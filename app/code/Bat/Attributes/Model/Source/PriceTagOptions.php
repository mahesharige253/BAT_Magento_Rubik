<?php

namespace Bat\Attributes\Model\Source;

class PriceTagOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Normal'), 'value' => '0'],
                ['label' => __('Price Tag'), 'value' => '1'],
                ['label' => __('UTC'), 'value' => '2'],
                ['label' => __('Price Tag Package'), 'value' => '3'],
            ];
        }
        return $this->_options;
    }
}
