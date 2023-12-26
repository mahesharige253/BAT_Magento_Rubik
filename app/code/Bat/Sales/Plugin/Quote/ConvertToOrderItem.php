<?php

namespace Bat\Sales\Plugin\Quote;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;

/**
 * @class ConvertToOrderItem
 * Plugin to add custom data in quote item to sales order item
 */
class ConvertToOrderItem
{
    /**
     * convert custom data from quote item to sales order item
     *
     * @param ToOrderItem $subject
     * @param \Closure $proceed
     * @param AbstractItem $item
     * @param array $additional
     * @return mixed
     */
    public function aroundConvert(
        ToOrderItem $subject,
        \Closure $proceed,
        AbstractItem $item,
        $additional = []
    ) {
        $orderItem = $proceed($item, $additional);
        $orderItem->setUom($item->getUom());
        $orderItem->setIsPriceTag($item->getIsPriceTag());
        $orderItem->setReturnSwiftReason($item->getReturnSwiftReason());
        $orderItem->setBaseToSecondaryUom($item->getBaseToSecondaryUom());
        return $orderItem;
    }
}
