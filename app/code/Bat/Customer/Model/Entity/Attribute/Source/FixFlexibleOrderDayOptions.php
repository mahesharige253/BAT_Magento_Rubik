<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class FixFlexibleOrderDayOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return [
            ['label' => __('--Please Select--'), 'value' => ''],
            ['label' => __('Fix'), 'value' => 'fix'],
            ['label' => __('Flexible'), 'value' => 'flexible']
        ];
    }
}
