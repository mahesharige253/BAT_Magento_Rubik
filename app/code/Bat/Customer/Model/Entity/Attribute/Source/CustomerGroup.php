<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class CustomerGroup extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('--Select Group--'), 'value' => ''],
                ['label' => __('Special Customer'), 'value' => 0],
                ['label' => __('Volume Customer'), 'value' => 1],
                ['label' => __('SKU/NPI Customer'), 'value' => 2]
            ];
        }
        return $this->_options;
    }
}
