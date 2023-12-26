<?php
namespace Bat\Discount\Model\Rule\Action;

use Magento\SalesRule\Model\Rule\Action\Discount\AbstractDiscount;

class SpecialCustomerQtyDiscount extends AbstractDiscount
{
    /**
     * Action name
     */
    public const ACTION_NAME = 'special_customer_qty_discount';

    /**
     * Get Action type
     *
     * @return string
     */
    public function getActionType()
    {
        return self::ACTION_NAME;
    }

    /**
     * Calculate
     *
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem[] $item
     * @param float $qty
     * @return array
     */
    public function calculate($rule, $item, $qty)
    {
        /** @var \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData */
        $discountData = $this->discountFactory->create();

        $baseDiscountAmount = (float) $rule->getDiscountAmount();
        $discountAmount = $this->priceCurrency->convert($baseDiscountAmount, $item->getQuote()->getStore());
        $itemDiscountAmount = $item->getDiscountAmount();
        $itemBaseDiscountAmount = $item->getBaseDiscountAmount();
        $itemPrice = $this->validator->getItemPrice($item);
        $baseItemPrice = $this->validator->getItemBasePrice($item);

        $discountAmountMin = min(($itemPrice * $qty) - $itemDiscountAmount, $discountAmount * $qty);
        $baseDiscountAmountMin = min(($baseItemPrice * $qty) - $itemBaseDiscountAmount, $baseDiscountAmount * $qty);
        $discountData->setAmount($discountAmountMin);
        $discountData->setBaseAmount($baseDiscountAmountMin);
        return $discountData;
    }
}
