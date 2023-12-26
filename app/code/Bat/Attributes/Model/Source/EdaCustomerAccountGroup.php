<?php

namespace Bat\Attributes\Model\Source;

/**
 * @class EdaCustomerAccountGroup
 * Options class for customer account group
 */
class EdaCustomerAccountGroup extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Select Customer Account Group'), 'value' => ''],
                ['label' => __('BAT Sold-to party'), 'value' => 'Z001'],
                ['label' => __('BAT Goods recipient'), 'value' => 'Z002'],
                ['label' => __('BAT Payer'), 'value' => 'Z003'],
                ['label' => __('BAT Bill-to party'), 'value' => 'Z004'],
                ['label' => __('BAT Employee'), 'value' => 'Z011'],
                ['label' => __('BAT Vendor Ship-to'), 'value' => 'Z102'],
                ['label' => __('BAT Inter Company'), 'value' => 'ZINT']
            ];
        }
        return $this->_options;
    }
}
