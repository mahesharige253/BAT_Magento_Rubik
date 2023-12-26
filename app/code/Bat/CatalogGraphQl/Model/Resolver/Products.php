<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Products\Query\ProductQueryInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Layer\Resolver;
use Bat\CatalogGraphQl\Model\Resolver\DataProvider\ProductItems;
use Psr\Log\LoggerInterface;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class Products implements ResolverInterface
{
    /**
     * @var ProductQueryInterface
     */
    private $searchQuery;

    /**
     * @var ProductItems
     */
    private $productItems;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ProductQueryInterface $searchQuery
     * @param ProductItems $productItems
     * @param GetCustomer $getCustomer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductQueryInterface $searchQuery,
        ProductItems $productItems,
        GetCustomer $getCustomer,
        LoggerInterface $logger
    ) {
        $this->searchQuery = $searchQuery;
        $this->productItems = $productItems;
        $this->getCustomer = $getCustomer;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }

        $this->validateInput($args);
        $areaCode = '';
        if (isset($args['areaCode'])) {
            $areaCode = $args['areaCode'];
        }
        $searchResult = $this->searchQuery->getResult($args, $info, $context);
        $this->logger->info('----- PLP -----');
        $this->logger->info('Total: '.$searchResult->getTotalCount());

        $frequentlyOrderedProductId = '';
        $sigunguCode = '';
        if (false !== $context->getExtensionAttributes()->getIsCustomer()) {
            $customer = $this->getCustomer->execute($context);
            $customAttributes = $customer->getCustomAttributes();
            if (isset($customAttributes['bat_frequently_ordered'])) {
                $frequentlyOrderedProductId = $customAttributes['bat_frequently_ordered']->getValue();
            }
            if (isset($customAttributes['sigungu_code'])) {
                $sigunguCode = $customAttributes['sigungu_code']->getValue();
            }
        }

        if ($searchResult->getCurrentPage() > $searchResult->getTotalPages() && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$searchResult->getCurrentPage(), $searchResult->getTotalPages()]
                )
            );
        }
        $categoryId = $args['filter']['category_id']['eq'];
        $pageSize = $args['pageSize'];
        $currentPage = $args['currentPage'];
        $proData = $this->productItems->getProductData(
            $categoryId,
            $pageSize,
            $currentPage,
            $frequentlyOrderedProductId,
            $sigunguCode
        );

        $proDataCount = $this->productItems->getProductCount(
            $categoryId,
            100000,
            1,
            $frequentlyOrderedProductId,
            $sigunguCode
        );

        $data = [
            'total_count' => $proDataCount,
            'items' => $proData,
            'suggestions' => $searchResult->getSuggestions(),
            'page_info' => [
                'page_size' => $searchResult->getPageSize(),
                'current_page' => $searchResult->getCurrentPage(),
                'total_pages' => $searchResult->getTotalPages()
            ],
            'search_result' => $searchResult,
            'layer_type' => isset($args['search']) ? Resolver::CATALOG_LAYER_SEARCH : Resolver::CATALOG_LAYER_CATEGORY,
            'area_code' => $areaCode
        ];

        if (isset($args['filter']['category_id'])) {
            $data['categories'] = $args['filter']['category_id']['eq'] ?? $args['filter']['category_id']['in'];
            $data['categories'] = is_array($data['categories']) ? $data['categories'] : [$data['categories']];
        }
        return $data;
    }

    /**
     * Validate input arguments
     *
     * @param array $args
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     */
    private function validateInput(array $args)
    {
        if (isset($args['searchAllowed']) && $args['searchAllowed'] === false) {
            throw new GraphQlAuthorizationException(__('Product search has been disabled.'));
        }
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        if (!isset($args['search']) && !isset($args['filter'])) {
            throw new GraphQlInputException(
                __("'search' or 'filter' input argument is required.")
            );
        }
    }
}
