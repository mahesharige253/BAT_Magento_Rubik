<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class OrderWeekOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return [
            ['label' => __('--Select Week--'), 'value' => ''],
            ['label' => __('Every Week'), 'value' => 'every'],
            ['label' => __('Even Week'), 'value' => 'even'],
            ['label' => __('Odd Week'), 'value' => 'odd'],
            ['label' => __('1st Week'), 'value' => 'week_one'],
            ['label' => __('2nd Week'), 'value' => 'week_two'],
            ['label' => __('3rd Week'), 'value' => 'week_three'],
            ['label' => __('4th Week'), 'value' => 'week_four'],
        ];
    }
}
