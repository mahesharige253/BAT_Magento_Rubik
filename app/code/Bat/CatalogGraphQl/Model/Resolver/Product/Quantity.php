<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class Quantity implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $_stockStatus;

    /**
     * @var \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * Construct method
     *
     * @param StockStateInterface $stockStatus
     * @param GetSalableQuantityDataBySku $getSalableQtyDataBySku
     */
    public function __construct(
        /**
         * @var \Magento\CatalogInventory\Api\StockStateInterface
         */
        StockStateInterface $stockStatus,
        GetSalableQuantityDataBySku $getSalableQtyDataBySku
    ) {
        $this->_stockStatus = $stockStatus;
        $this->getSalableQuantityDataBySku = $getSalableQtyDataBySku;
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
        $product = $value['model'];
        $salableQtyData = $this->getSalableQuantityDataBySku->execute($product->getSku());
        if (isset($salableQtyData[0])) {
            $saleableQty = (int) $salableQtyData[0]['qty'];
        }

        return $saleableQty;
    }
}
