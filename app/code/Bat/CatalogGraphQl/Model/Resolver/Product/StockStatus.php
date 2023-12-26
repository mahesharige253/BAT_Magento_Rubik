<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Catalog\Model\ProductRepository;

class StockStatus implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

     /**
     * @var ProductRepository
     */
    protected $productRepository;

     /**
      * Construct method
      *
      * @param StockRegistryInterface $stockRegistry
      * @param GetSalableQuantityDataBySku $getSalableQtyDataBySku
      * @param ProductRepository $productRepository
      */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        GetSalableQuantityDataBySku $getSalableQtyDataBySku,
        ProductRepository $productRepository
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->getSalableQuantityDataBySku = $getSalableQtyDataBySku;
        $this->productRepository = $productRepository;
    }

    /**
     * Resolve method
     *
     * @param Field $field
     * @param Context $context
     * @param ResolveInfo $info
     * @param Array $value
     * @param Array $args
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $saleableQty = 0;
        $formattedPrice = '';
        $product = $value['model'];
        $salableQtyData = $this->getSalableQuantityDataBySku->execute($product['sku']);
        if (isset($salableQtyData[0])) {
            $saleableQty = $salableQtyData[0]['qty'];
        }
        $stockStatus = $this->getStockStatus($product->getId());
        $product = $this->productRepository->get($product['sku']);
        $isPriceTag = 0;
        if($product->getCustomAttribute('pricetag_type')) {
        $isPriceTag = $product->getCustomAttribute('pricetag_type')->getValue();
        }
        $isInStock = __('In Stock');
        if (!in_array($isPriceTag, [1, 2, 3])) {
        if ($saleableQty < 10 && $stockStatus != 'is_in_stock') {
            $isInStock = __('Out of Stock');
        }
    }
        return $isInStock;
    }

    /**
     * Get Stock status
     *
     * @param int $productId
     * @return bool|int
     * return stock status of a product
     */
    public function getStockStatus($productId)
    {
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $isInStock = $stockItem ? $stockItem->getIsInStock() : false;
        return $isInStock;
    }
}
