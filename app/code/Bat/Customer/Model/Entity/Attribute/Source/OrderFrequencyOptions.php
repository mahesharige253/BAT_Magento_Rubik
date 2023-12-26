<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class OrderFrequencyOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return $this->_options = [
            ['label' => __('Weekly'), 'value' => '0'],
            ['label' => __('Bi-Weekly'), 'value' => '1'],
            ['label' => __('Monthly'), 'value' => '2'],
            ['label' => __('Bi-Monthly'), 'value' => '3'],
        ];
    }
}
