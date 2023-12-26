<?php

namespace Bat\Sales\Block\Adminhtml\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * @class CreditInfo
 * Add credit information to order totals
 */
class CreditInfo extends Template
{
    /**
     * Add OverPayment and Remaining AR to sales order view
     *
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_order = $parent->getOrder();
        $this->_source = $parent->getSource();
        $discountAmount = abs($this->_order->getDiscountAmount());
        $subTotal = $this->getSubTotal($this->_order);

        $sort = 'tax';
        if ($discountAmount) {
            $sort = 'discount';
        }
        $parent->removeTotal('due');
        $parent->removeTotal('paid');
        $parent->removeTotal('shipping');
        $parent->removeTotal('tax');
        $parent->removeTotal('subtotal');
        $parent->removeTotal('discount');

        $parent->addTotal(new DataObject(
            [
                'code' => 'subtotal',
                'value' => $subTotal,
                'base_value' => $subTotal,
                'label' => __('Subtotal With VAT'),
            ]
        ), 'first');

        $discountedSubtotal = $subTotal - $discountAmount;
        $newSubtotal = round($discountedSubtotal / 1.1);
        $taxValue = $discountedSubtotal - $newSubtotal;
        $discountedSubtotal = $discountedSubtotal -$taxValue;

        $parent->addTotal(new DataObject(
            [
                'code' => 'subtotal_without_vat',
                'strong' => false,
                'value' => $discountedSubtotal,
                'label' => __('Net Amount'),
            ]
        ), 'subtotal');

        $parent->addTotal(new DataObject(
            [
                'code' => 'tax',
                'strong' => false,
                'value' => $this->_order->getTaxAmount(),
                'label' => __('VAT'),
            ]
        ), 'subtotal_without_vat');

        /**
         * Add discount
         */
        if ((double)$this->_order->getDiscountAmount() != 0) {
            if ($this->_order->getDiscountDescription()) {
                $discountLabel = __('Discount (%1)', $this->_order->getDiscountDescription());
            } else {
                $discountLabel = __('Discount');
            }
            $parent->addTotal(new DataObject(
                [
                    'code' => 'discount',
                    'value' => $this->_order->getDiscountAmount(),
                    'base_value' => $this->_order->getBaseDiscountAmount(),
                    'label' => $discountLabel,
                ]
            ), 'tax');
        }

        $parent->addTotal(new DataObject(
            [
                'code' => 'remaining_ar',
                'strong' => false,
                'value' => $this->_order->getRemainingAr(),
                'label' => __('Remaining AR'),
            ]
        ), $sort);

        $parent->addTotal(new DataObject(
            [
                'code' => 'over_payment',
                'strong' => false,
                'value' => $this->_order->getOverPayment(),
                'label' => __('Over Payment'),
            ]
        ), 'remaining_ar');

        $parent->addTotal(new \Magento\Framework\DataObject(
            [
                'code' => 'paid',
                'strong' => true,
                'value' => $this->_order->getTotalPaid(),
                'base_value' => $this->_order->getBaseTotalPaid(),
                'label' => __('Paid From Credit'),
            ]
        ), 'over_payment');

        $parent->addTotal(new \Magento\Framework\DataObject(
            [
                'code' => 'due',
                'strong' => true,
                'value' => $this->_order->getTotalDue(),
                'base_value' => $this->_order->getBaseTotalDue(),
                'label' => __('Minimum Payable'),
            ]
        ), 'paid');

        $parent->addTotal(new DataObject(
            [
                'code' => 'grand_total',
                'strong' => true,
                'value' => $this->_order->getGrandTotal(),
                'base_value' => $this->_order->getBaseGrandTotal(),
                'label' => __('Grand Total'),
            ]
        ), 'due');

        return $this;
    }

    /**
     * Get Order SubTotal
     *
     * @param $order
     * @return int|mixed
     */
    public function getSubTotal($order)
    {
        $subTotal = 0;
        foreach ($order->getAllItems() as $item) {
            $subTotal = $subTotal + $item->getRowTotal();
        }
        return $subTotal;
    }
}
