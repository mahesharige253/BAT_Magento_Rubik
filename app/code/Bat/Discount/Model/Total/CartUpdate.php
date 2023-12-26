<?php
namespace Bat\Discount\Model\Total;

use Magento\SalesRule\Model\RuleFactory;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Bat\Discount\Model\Rule\Action\SpecialCustomerQtyDiscount;

class CartUpdate extends AbstractTotal
{

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        RuleFactory $ruleFactory
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Collect  Quote data.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        $appliedRuleIds = $quote->getAppliedRuleIds();
        $itemQty = $this->getItemQty($quote);
        if ($appliedRuleIds) {
            $appliedRuleIds = explode(',', $appliedRuleIds);
            foreach ($appliedRuleIds as $appliedRuleId) {
                $rule = $this->ruleFactory->create()->load($appliedRuleId);
                if ($rule->getSimpleAction() == SpecialCustomerQtyDiscount::ACTION_NAME) {
                    if ($rule->getDiscountQty() > $itemQty) {
                        $customDiscount = $itemQty * $rule->getDiscountAmount();
                    } else {
                           $customDiscount = $rule->getDiscountQty() * $rule->getDiscountAmount();
                    }
                    $discount =  $this->priceCurrency->convert($customDiscount);
                    $total->setTotalAmount('discount', -$discount);
                    $total->setBaseTotalAmount('discount', -$customDiscount);
                    $total->setBaseGrandTotal($total->getBaseGrandTotal() - $customDiscount);
                    $quote->setDiscount(-$discount);
                }
            }
        }
        return $this;
    }

    /**
     * Get item qty
     *
     * @param object $quote
     * @return int
     */
    protected function getItemQty($quote)
    {
        $qty = 0;
        foreach ($quote->getAllItems() as $item) {
            if (!($item->getIsPriceTag())) {
                $qty += $item->getQty();
            }
        }
        return $qty;
    }
}
