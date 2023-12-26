<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\DataProvider;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Bat\BestSellers\Model\GetBestSellers;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class ProductItems
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var StoreManagerInterface;
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var GetBestSellers
     */
    private GetBestSellers $getBestSellers;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param GetBestSellers $getBestSellers
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        GetBestSellers $getBestSellers
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->getBestSellers = $getBestSellers;
    }

    /**
     * @inheritdoc
     */
    public function getProductData($categoryId, $pageSize, $currentPage, $frequentlyOrderedProductId, $sigunguCode)
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $rootNodeId = $this->storeManager->getStore($storeId)->getRootCategoryId();

        if ($categoryId != $rootNodeId) {
            $otherProducts = $this->getOtherProducts($categoryId, []);

            return $this->getProductList(
                $otherProducts,
                $pageSize,
                $currentPage,
                $frequentlyOrderedProductId,
                [],
                $sigunguCode
            );
        } else {
            $newProductsIds = [];
            $bestSellerProductIds = [];

            //To get the best seller products data
            $bestSellerProductIds= $this->getBestSellers->getBestSellers($sigunguCode);

            //Get New products id
            $newProductCollectionData = $this->productCollectionFactory->create();
            $newProductCollectionData->addFieldToSelect('entity_id')
                ->addAttributeToFilter('product_tag', [['eq'=>'1,2'],['eq'=>'1']])
                ->addAttributeToFilter('is_plp', 1)
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);
            foreach ($newProductCollectionData as $newProduct) {
                $catIdsArray = $newProduct->getCategoryIds();
                if (in_array($categoryId, $catIdsArray)) {
                    $newProductsIds[] = $newProduct->getEntityId();
                    foreach ($bestSellerProductIds as $key => $bestSellerProductId) {
                        if($bestSellerProductId == $newProduct->getEntityId()) {
                            unset($bestSellerProductIds[$key]);
                        }
                    }
                }
            }

            $this->logger->info('New product Ids from plp', $newProductsIds);
            $this->logger->info('Second best seller data plp', $bestSellerProductIds);

            // Get New products collection
            $newProducts = [];
            $category = $this->categoryFactory->create()->load($categoryId);
            $collectionNewProduct = $this->productCollectionFactory->create();
            $collectionNewProduct->addAttributeToSelect('*');
            $collectionNewProduct->addCategoryFilter($category);
            $collectionNewProduct->addAttributeToFilter('entity_id', ['in' => $newProductsIds]);
            $collectionNewProduct->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collectionNewProduct->addAttributeToFilter('is_plp', 1);
            $collectionNewProduct->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);
            $collectionNewProduct->addAttributeToSort('position', 'ASC');

            $newProducts = $collectionNewProduct->getData();
            $this->logger->info('New Products: '.count($newProducts));

            // Get Best Seller Products
            $collectionBestSellerProduct = $this->productCollectionFactory->create();
            $collectionBestSellerProduct->addAttributeToSelect('*');
            $collectionBestSellerProduct->addCategoriesFilter(['in' => $categoryId]);
            $collectionBestSellerProduct->addAttributeToFilter('entity_id', ['in' => $bestSellerProductIds]);
            $collectionBestSellerProduct->addAttributeToFilter('entity_id', ['nin' => $newProductsIds]);
            $collectionBestSellerProduct->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collectionBestSellerProduct->addAttributeToFilter(
                'visibility',
                ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]
            );

            $bestSellerProducts = $collectionBestSellerProduct->getData();
            $bsData = [];
            foreach ($collectionBestSellerProduct->getData() as $bestSellersVal) {
                $bsData[$bestSellersVal['entity_id']] = $bestSellersVal;
            }

            $finalBestSellerData = [];
            foreach ($bestSellerProductIds as $bestsellerSequenceId) {
                if (isset($bsData[$bestsellerSequenceId])) {
                    $finalBestSellerData[] = $bsData[$bestsellerSequenceId];
                }
            }
            $this->logger->info('Best Seller Products: '.count($finalBestSellerData));
            $newAndBestSellerdata = array_merge($newProducts, $finalBestSellerData);
            $this->logger->info('BestSeller and New Products: '.count($newAndBestSellerdata));

            $newAndBestSellerProductIds = array_merge($newProductsIds, $bestSellerProductIds);
            $this->logger->info('BestSeller and New Products IDs: '.count($newAndBestSellerProductIds));
            //Get other products
            $otherProducts = $this->getOtherProducts($categoryId, $newAndBestSellerProductIds);
            $data = array_merge($newAndBestSellerdata, $otherProducts);
            return $this->getProductList(
                $data,
                $pageSize,
                $currentPage,
                $frequentlyOrderedProductId,
                $bestSellerProductIds,
                $sigunguCode
            );
        }
    }

    /**
     * Get Other products
     *
     * @param string $categoryId
     * @param array $excludedProductIds
     * @return array
     */
    public function getOtherProducts($categoryId, $excludedProductIds)
    {
        $category = $this->categoryFactory->create()->load($categoryId);
        $collectionOtherProduct = $this->productCollectionFactory->create();
        $collectionOtherProduct->addCategoryFilter($category);
        if (!empty($excludedProductIds)) {
            $collectionOtherProduct->addAttributeToFilter('entity_id', ['nin' => $excludedProductIds]);
        }
        $collectionOtherProduct->addAttributeToFilter('is_plp', 1);
        $collectionOtherProduct->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collectionOtherProduct->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);
        $collectionOtherProduct->setOrder('position', 'ASC');

        return $collectionOtherProduct->getData();
    }

    /**
     * Get Other products
     *
     * @param array $productData
     * @param string $pageSize
     * @param string $currentPage
     * @param string $frequentlyOrderedProductId
     * @param array $bestSellerProductIds
     * @return array
     */
    public function getProductList(
        $productData,
        $pageSize,
        $currentPage,
        $frequentlyOrderedProductId,
        $bestSellerProductIds,
        $sigunguCode
    ) {
        $offset = ($currentPage - 1) * $pageSize;
        $dataItems = array_slice($productData, $offset, $pageSize);
        $bestSellerProductIds= $this->getBestSellers->getBestSellers($sigunguCode);

        foreach ($dataItems as $key => $value) {
            $productTag = [];
            if (isset($bestSellerProductIds) && in_array($value['entity_id'], $bestSellerProductIds)) {
                $productTag[] = 3;
            }
            if ($value['entity_id'] == $frequentlyOrderedProductId) {
                $productTag[] = 4;
            }
            if (!empty($productTag)) {
                $dataItems[$key]['product_tag'] = implode(',', $productTag);
            }
        }
        $this->logger->info('Total Items: '.count($dataItems));
        return $dataItems;
    }

    /**
     * Get Products count
     *
     * @param string $categoryId
     * @param string $pageSize
     * @param string $currentPage
     * @param string $frequentlyOrderedProductId
     * @param array $sigunguCode
     * @return string
     */
    public function getProductCount($categoryId, $pageSize, $currentPage, $frequentlyOrderedProductId, $sigunguCode)
    {
        return count($this->getProductData($categoryId, $pageSize, $currentPage, $frequentlyOrderedProductId, $sigunguCode));
    }
}
