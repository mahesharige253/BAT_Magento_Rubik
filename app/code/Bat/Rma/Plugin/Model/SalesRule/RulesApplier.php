<?php

namespace Bat\Rma\Plugin\Model\SalesRule;

use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

/**
 * @class RulesApplier
 * Remove discounts for return related orders
 */
class RulesApplier
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $ruleCollection;

    /**
     * @param CollectionFactory $rulesFactory
     */
    public function __construct(
        CollectionFactory $rulesFactory
    ) {
        $this->ruleCollection = $rulesFactory;
    }

    /**
     * Remove applied rules for order type IRO and ZRE1
     *
     * @param \Magento\SalesRule\Model\RulesApplier $subject
     * @param $item
     * @param $rules
     * @param $skipValidation
     * @param $couponCode
     * @return array
     */
    public function beforeApplyRules(
        \Magento\SalesRule\Model\RulesApplier $subject,
        $item,
        $rules,
        $skipValidation,
        $couponCode
    ) {
        if ($item->getQuote()->getIsReturnOrder()) {
            $rules = [];
        }
        return [$item, $rules, $skipValidation, $couponCode];
    }
}
