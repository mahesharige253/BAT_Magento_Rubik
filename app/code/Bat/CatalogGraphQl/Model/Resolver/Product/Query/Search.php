<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product\Query;

use Magento\CatalogGraphQl\DataProvider\Product\SearchCriteriaBuilder;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Search\QueryPopularity;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResultFactory;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsProcessorInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Search\Api\SearchInterface;
use Magento\Search\Model\Search\PageSizeProvider;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\FieldSelection;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\Suggestions;
use Magento\Sales\Model\ResourceModel\Report\Bestsellers\CollectionFactory as BestSellersCollectionFactory;

/**
 * Full text search for catalog using given search criteria.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Search extends \Magento\CatalogGraphQl\Model\Resolver\Products\Query\Search
{
    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var PageSizeProvider
     */
    private $pageSizeProvider;

    /**
     * @var FieldSelection
     */
    private $fieldSelection;

    /**
     * @var ArgumentsProcessorInterface
     */
    private $argsSelection;

    /**
     * @var ProductSearch
     */
    private $productsProvider;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var Suggestions
     */
    private $suggestions;

    /**
     * @var QueryPopularity
     */
    private $queryPopularity;

    /**
     * @var GetCustomer
     */
    private GetCustomer $getCustomer;

    /**
     * @var BestSellersCollectionFactory
     */
    private BestSellersCollectionFactory $_bestSellersCollectionFactory;

    /**
     * @param SearchInterface $search
     * @param SearchResultFactory $searchResultFactory
     * @param PageSizeProvider $pageSize
     * @param FieldSelection $fieldSelection
     * @param ProductSearch $productsProvider
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetCustomer $getCustomer
     * @param BestSellersCollectionFactory $bestSellersCollectionFactory
     * @param ArgumentsProcessorInterface|null $argsSelection
     * @param Suggestions|null $suggestions
     * @param QueryPopularity|null $queryPopularity
     */
    public function __construct(
        SearchInterface $search,
        SearchResultFactory $searchResultFactory,
        PageSizeProvider $pageSize,
        FieldSelection $fieldSelection,
        ProductSearch $productsProvider,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetCustomer $getCustomer,
        BestSellersCollectionFactory $bestSellersCollectionFactory,
        ArgumentsProcessorInterface $argsSelection = null,
        Suggestions $suggestions = null,
        QueryPopularity $queryPopularity = null
    ) {
        $this->search = $search;
        $this->searchResultFactory = $searchResultFactory;
        $this->pageSizeProvider = $pageSize;
        $this->fieldSelection = $fieldSelection;
        $this->productsProvider = $productsProvider;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getCustomer = $getCustomer;
        $this->_bestSellersCollectionFactory = $bestSellersCollectionFactory;
        $this->argsSelection = $argsSelection ?: ObjectManager::getInstance()
            ->get(ArgumentsProcessorInterface::class);
        $this->suggestions = $suggestions ?: ObjectManager::getInstance()
            ->get(Suggestions::class);
        $this->queryPopularity = $queryPopularity ?: ObjectManager::getInstance()->get(QueryPopularity::class);
    }

    /**
     * Return product search results using Search API
     *
     * @param array $args
     * @param ResolveInfo $info
     * @param ContextInterface $context
     * @return SearchResult
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     */
    public function getResult(
        array $args,
        ResolveInfo $info,
        ContextInterface $context
    ): SearchResult {
        $frequentlyOrderedProductId = '';
        if (false !== $context->getExtensionAttributes()->getIsCustomer()) {
            $customer = $this->getCustomer->execute($context);
            $customAttributes = $customer->getCustomAttributes();
            if (isset($customAttributes['bat_frequently_ordered'])) {
                $frequentlyOrderedProductId = $customAttributes['bat_frequently_ordered']->getValue();
            }
        }
        $areaCode = '';
        if (isset($args['areaCode'])) {
            $areaCode = $args['areaCode'];
        }
        $searchCriteria = $this->buildSearchCriteria($args, $info);
        $realPageSize = $searchCriteria->getPageSize();
        $realCurrentPage = $searchCriteria->getCurrentPage();
        //Because of limitations of sort and pagination on search API we will query all IDS
        $pageSize = $this->pageSizeProvider->getMaxPageSize();
        $searchCriteria->setPageSize($pageSize);
        $searchCriteria->setCurrentPage(0);
        $itemsResults = $this->search->search($searchCriteria);

        //Address limitations of sort and pagination on search API apply original pagination from GQL query
        $searchCriteria->setPageSize($realPageSize);
        $searchCriteria->setCurrentPage($realCurrentPage);
        $fieldsSelected = $this->fieldSelection->getProductsFieldSelection($info);
        if (in_array('product_tags', $fieldsSelected)) {
            unset($fieldsSelected['product_tags']);
            $fieldsSelected[] = 'product_tag';
            if ($areaCode != '' && !in_array('bat_product_area_code', $fieldsSelected)) {
                $fieldsSelected[] = 'bat_product_area_code';
            }
        }
        $searchResults = $this->productsProvider->getList(
            $searchCriteria,
            $itemsResults,
            $fieldsSelected,
            $context
        );

        $totalPages = $realPageSize ? ((int)ceil($searchResults->getTotalCount() / $realPageSize)) : 0;

        // add query statistics data
        if (!empty($args['search'])) {
            $this->queryPopularity->execute($context, $args['search'], (int) $searchResults->getTotalCount());
        }

        $productArray = [];
        $bestSellerReferenceId = [];
        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($searchResults->getItems() as $product) {
            $productData = $product->getData();
            $productId = $product->getId();
            if ($areaCode != '') {
                if (isset($productData['bat_product_area_code'])) {
                    if ($areaCode == $productData['bat_product_area_code']) {
                        $bestSellerReferenceId[] = $productId;
                    }
                }
            }
            $productArray[$product->getId()] = $product->getData();
            if ($product->getId() == $frequentlyOrderedProductId) {
                $productArray[$product->getId()]['frequent'] = $frequentlyOrderedProductId;
            }
            $productArray[$product->getId()]['model'] = $product;
        }

        if (!empty($bestSellerReferenceId)) {
            $bestSellers = $this->getBestSellers($bestSellerReferenceId);
            if ($bestSellers->count()) {
                foreach ($bestSellers as $bestSeller) {
                    $bestSellerProductId = $bestSeller->getProductId();
                    $productArray[$bestSellerProductId]['best_seller'] = $bestSellerProductId;
                }
            }
        }

        $suggestions = [];
        $totalCount = (int) $searchResults->getTotalCount();
        if ($totalCount === 0 && !empty($args['search'])) {
            $suggestions = $this->suggestions->execute($context, $args['search']);
        }

        return $this->searchResultFactory->create(
            [
                'totalCount' => $totalCount,
                'productsSearchResult' => $productArray,
                'searchAggregation' => $itemsResults->getAggregations(),
                'pageSize' => $realPageSize,
                'currentPage' => $realCurrentPage,
                'totalPages' => $totalPages,
                'suggestions' => $suggestions,
            ]
        );
    }

    /**
     * Build search criteria from query input args
     *
     * @param array $args
     * @param ResolveInfo $info
     * @return SearchCriteriaInterface
     */
    private function buildSearchCriteria(array $args, ResolveInfo $info): SearchCriteriaInterface
    {
        $productFields = (array)$info->getFieldSelection(1);
        $includeAggregations = isset($productFields['filters']) || isset($productFields['aggregations']);
        $fieldName = $info->fieldName ?? "";
        $processedArgs = $this->argsSelection->process((string) $fieldName, $args);
        $searchCriteria = $this->searchCriteriaBuilder->build($processedArgs, $includeAggregations);

        return $searchCriteria;
    }

    /**
     * Return best seller collection
     *
     * @param array $bestSellerReferenceId
     * @return \Magento\Sales\Model\ResourceModel\Report\Bestsellers\Collection
     */
    public function getBestSellers($bestSellerReferenceId)
    {
        $bestSellers = $this->_bestSellersCollectionFactory->create()->setPeriod('month');
        $bestSellers->addFieldToFilter('product_id', ['in'=>$bestSellerReferenceId]);
        return $bestSellers;
    }
}
