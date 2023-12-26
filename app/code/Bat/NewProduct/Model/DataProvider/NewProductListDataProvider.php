<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\NewProduct\Model\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\BestSellers\Helper\Data as BestSellersHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Bat\NewProduct\Model\ResourceModel\NewProductResource\CollectionFactory as NewProductsCollectionFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Bat\BestSellers\Model\GetBestSellers;

/**
 * @class NewProductListDataProvider
 * New Products data provider
 */
class NewProductListDataProvider
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var NewProductsCollectionFactory
     */
    private NewProductsCollectionFactory $newProductsCollectionFactory;

    /**
     * @var BestSellersHelper
     */
    private BestSellersHelper $bestSellersHelper;

    /**
     * @var GetBestSellers
     */
    private GetBestSellers $getBestSellers;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param NewProductsCollectionFactory $newProductsCollectionFactory
     * @param BestSellersHelper $bestSellersHelper
     * @param GetBestSellers $getBestSellers
     */
    public function __construct(
        CollectionFactory   $productCollectionFactory,
        ScopeConfigInterface    $scopeConfig,
        LoggerInterface  $logger,
        NewProductsCollectionFactory    $newProductsCollectionFactory,
        BestSellersHelper   $bestSellersHelper,
        GetBestSellers $getBestSellers
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->newProductsCollectionFactory = $newProductsCollectionFactory;
        $this->bestSellersHelper = $bestSellersHelper;
        $this->getBestSellers = $getBestSellers;
    }

    /**
     * Return New Products List
     *
     * @param CustomerInterface $customer
     * @param String $areaCode
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    public function getNewProductList($customer, $areaCode)
    {
        $result = [];
        $carouselEnabled = $this->scopeConfig->getValue(
            'product_carousel/general/new_products_carousel'
        );
        if ($carouselEnabled) {
            $customAttributes = $customer->getCustomAttributes();
            $frequentlyOrderedProductId = $this->getFrequentlyOrdered($customAttributes);
            $newProductIds = [];
            $newProductCollectionData = $this->newProductsCollectionFactory->create();
            foreach ($newProductCollectionData as $newProduct) {
                $newProductIds[] = $newProduct->getProductId();
            }
            if (!empty($newProductIds)) {
                $productCount = $this->scopeConfig->getValue(
                    'product_carousel/general/new_products_carousel_limit'
                );
                $newProductList = $this->getNewProductCollection($newProductIds, $productCount);
                if ($newProductList->count()) {
                    $carouselTitle = $this->scopeConfig->getValue(
                        'product_carousel/general/new_products_carousel_title'
                    );
                    $result['title'] = $carouselTitle;
                    $productArray = [];
                    foreach ($newProductList->getItems() as $product) {
                        $productData = $product->getData();
                        $productId = $product->getId();
                        $productArray[$productId] = $productData;
                        $productArray[$productId]['newproducts_carousel'] = true;
                        if ($productId == $frequentlyOrderedProductId) {
                            $productArray[$productId]['frequent'] = $frequentlyOrderedProductId;
                        }
                        $productArray[$productId]['model'] = $product;
                    }
                    $bestSellerReferenceId = $this->getBestSellers->getBestSellers($areaCode);
                    $this->logger->info('New products Carousel id', $newProductIds);
                    $this->logger->info('Best seller Ids', $bestSellerReferenceId);
                    foreach ($bestSellerReferenceId as $bestSellerId) {
                        if (isset($productArray[$bestSellerId])) {
                            $productArray[$bestSellerId]['best_seller'] = $bestSellerId;
                        }
                    }
                    $result['items'] = $productArray;
                }
            }
        } else {
            throw new GraphQlNoSuchEntityException(__('New Product Carousel disabled'));
        }
        if (empty($result)) {
            throw new GraphQlNoSuchEntityException(__('No New Products'));
        }
        return $result;
    }

    /**
     * Return new product collection
     *
     * @param array $newProductIds
     * @param string $productCount
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getNewProductCollection($newProductIds, $productCount)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('status', ['eq'=>1])
            ->addAttributeToFilter('is_plp', ['eq'=>1])
            ->addIdFilter($newProductIds)
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->setOrder('created_at', 'desc')
            ->setPageSize($productCount);
        return $collection;
    }

    /**
     * Return frequently ordered product id
     *
     * @param array $customAttributes
     * @return mixed|string
     */
    public function getFrequentlyOrdered($customAttributes)
    {
        $frequentlyOrderedProductId = '';
        if (isset($customAttributes['bat_frequently_ordered'])) {
            $frequentlyOrderedProductId = $customAttributes['bat_frequently_ordered']->getValue();
        }
        return $frequentlyOrderedProductId;
    }

    /**
     * Check if area code matches on the request
     *
     * @param string $areaCode
     * @param array $bestSellerReferenceId
     * @param array $productData
     * @param Int $productId
     * @return mixed
     */
    public function checkMatchingAreaCode($areaCode, $bestSellerReferenceId, $productData, $productId)
    {
        if (isset($productData['bat_product_area_code'])) {
            if ($areaCode == $productData['bat_product_area_code']) {
                $bestSellerReferenceId[] = $productId;
            }
        }
        return $bestSellerReferenceId;
    }
}
