<?php

namespace Bat\CatalogGraphQl\Model\Adapter\BatchDataMapper;

use Magento\AdvancedSearch\Model\Adapter\DataMapper\AdditionalFieldsProviderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @class PlpSortStockDataProvider
 * Data mapping for custom sort fields
 */

class PlpSortStockDataProvider implements AdditionalFieldsProviderInterface
{
    /**
     * Storemanager variable
     *
     * @var array
     */
    protected $storeManager;

    /**
     * ProductRepositoryInterface
     *
     */
    protected $productRepository;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function getFields(array $productIds, $storeId)
    {
        $fields = [];
        foreach ($productIds as $productId) {
            $productInfo = $this->productRepository->getById($productId);
            $in_stock=0;
            $createdAt = strtotime($productInfo->getCreatedAt());
            $sku = $productInfo->getSku();
            if ($sku) {
                $websiteCode = $this->storeManager->getStore($storeId)->getWebsite()->getCode();
                $in_stock = $productInfo->getStockStatus();
            }
            $fields[$productId]=['bat_created_at' => $createdAt];
        }
            return $fields;
    }
}
