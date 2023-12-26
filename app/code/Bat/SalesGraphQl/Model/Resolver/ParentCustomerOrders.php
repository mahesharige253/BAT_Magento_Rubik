<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesGraphQl\Model\Resolver\CustomerOrders\Query\OrderFilter;
use Magento\SalesGraphQl\Model\Resolver\CustomerOrders\Query\OrderSort;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;
use Bat\SalesGraphQl\Model\Resolver\DataProvider\ParentOrderDetail;

/**
 * Orders data resolver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ParentCustomerOrders implements ResolverInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderFilter
     */
    private $orderFilter;

    /**
     * @var OrderSort
     */
    private $orderSort;

    /**
     * @var StoreManagerInterface|mixed|null
     */
    private $storeManager;

    /**
     * @var FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var OrderFormatter
     */
    private $orderFormatter;

    /**
     * @var ParentOrderDetail
     */
    private $parentOrderDetail;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderFilter $orderFilter
     * @param OrderSort $orderSort
     * @param StoreManagerInterface|null $storeManager
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param FilterBuilder $filterBuilder
     * @param OrderFormatter $orderFormatter
     * @param ParentOrderDetail $parentOrderDetail
     */

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderFilter $orderFilter,
        OrderSort $orderSort,
        StoreManagerInterface $storeManager = null,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder,
        OrderFormatter $orderFormatter,
        ParentOrderDetail $parentOrderDetail
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderFilter = $orderFilter;
        $this->orderSort = $orderSort;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->orderFormatter = $orderFormatter;
        $this->parentOrderDetail = $parentOrderDetail;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        $storeIds = [];
        $userId = $context->getUserId();
        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();
        if (isset($args['scope'])) {
            $storeIds = $this->getStoresByScope($args['scope'], $store);
        }

        if (isset($args['filter']['number']) && ($args['filter']['number']!='')) {
            try {
                $searchResult = $this->getSearchResult($args, (int)$userId, (int)$store->getId(), $storeIds);
                $maxPages = (int)ceil($searchResult->getTotalCount() / $searchResult->getPageSize());
            } catch (InputException $e) {
                throw new GraphQlInputException(__($e->getMessage()));
            }

            $bulkOrderId = $args['filter']['number']['eq'];
            
            $ordersArray = [];
            foreach ($searchResult->getItems() as $orderModel) {
                $ordersArray[] = $this->orderFormatter->format($orderModel);
            }

            $websiteId = $context->getExtensionAttributes()->getStore()->getWebsiteId();
            $itemsData = $this->parentOrderDetail->getParentOrderDetail($bulkOrderId, $userId, $websiteId);

            return [
                'total_count' => $searchResult->getTotalCount(),
                'items' => $itemsData,
                'page_info' => [
                    'page_size' => $searchResult->getPageSize(),
                    'current_page' => $searchResult->getCurPage(),
                    'total_pages' => $maxPages,
                ]
            ];
        }
    }

    /**
     * Get search result from graphql query arguments
     *
     * @param array $args
     * @param int $userId
     * @param int $storeId
     * @param array $storeIds
     * @return OrderSearchResultInterface
     * @throws InputException
     */
    private function getSearchResult(array $args, int $userId, int $storeId, array $storeIds)
    {
        $filterGroups = $this->orderFilter->createFilterGroups($args, $userId, (int)$storeId, $storeIds);
        $this->searchCriteriaBuilder->setFilterGroups($filterGroups);
        if (isset($args['currentPage'])) {
            $this->searchCriteriaBuilder->setCurrentPage($args['currentPage']);
        }
        if (isset($args['pageSize'])) {
            $this->searchCriteriaBuilder->setPageSize($args['pageSize']);
        }
        if (isset($args['sort'])) {
            $sortOrders = $this->orderSort->createSortOrders($args);
            $this->searchCriteriaBuilder->setSortOrders($sortOrders);
        }

        if (isset($args['filter']['number']) && ($args['filter']['number'])) {
            return $this->orderRepository->getList($this->searchCriteriaBuilder->create());
        } else {
            $filterStatus = $args['filter']['status'];

             $filters = $this->filterBuilder
                ->setField('status')
                ->setValue($filterStatus)
                ->setConditionType("eq")->create();

            $filterGroup = $this->filterGroupBuilder
                ->addFilter($filters)
                ->create();
            $finalFilterList[] = $filterGroup;

            $searchCriteria = $this->searchCriteriaBuilder
                ->setFilterGroups($finalFilterList)
                ->create();
            return $this->orderRepository->getList($this->searchCriteriaBuilder->create());

        }
    }

    /**
     * Get eligible store to filter by based on scope
     *
     * @param string $scope
     * @param StoreInterface $store
     * @return array
     */
    private function getStoresByScope(string $scope, StoreInterface $store): array
    {
        $storeIds = [];
        switch ($scope) {
            case 'GLOBAL':
                $storeIds = $this->getStoresByFilter(null, null);
                break;
            case 'WEBSITE':
                    $websiteId = $store->getWebsiteId();
                    $storeIds = $this->getStoresByFilter((int)$websiteId, null);
                break;
            case 'STORE':
                    $storeGroupId = $store->getStoreGroupId();
                    $storeIds = $this->getStoresByFilter(null, (int)$storeGroupId);
                break;
            default:
                break;
        }
        return $storeIds;
    }

    /**
     * Filter store ids based on selected scope
     *
     * @param int|null $websiteId
     * @param int|null $storeGroupId
     * @return array
     */
    private function getStoresByFilter(?int $websiteId, ?int $storeGroupId): array
    {
        $stores = $this->storeManager->getStores(true, true);
        $storeIds = [];
        foreach ($stores as $store) {
            if (isset($websiteId) && $websiteId === (int)$store->getWebsiteId()
                ||
                isset($storeGroupId) && $storeGroupId === (int)$store->getStoreGroupId()
            ) {
                $storeIds[] = $store->getId();
            } elseif (!isset($websiteId) && !isset($storeGroupId)) {
                $storeIds[] = $store->getId();
            }
        }
        return $storeIds;
    }
}
