<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class OrderMonthOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return [
            ['label' => __('--Select Month--'), 'value' => ''],
            ['label' => __('Every Month'), 'value' => 'every'],
            ['label' => __('Even Month'), 'value' => 'even'],
            ['label' => __('Odd Month'), 'value' => 'odd'],
        ];
    }
}
