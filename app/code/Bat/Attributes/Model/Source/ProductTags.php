<?php

namespace Bat\Attributes\Model\Source;

class ProductTags extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('New'), 'value' => '1'],
                ['label' => __('Limited'), 'value' => '2'],
            ];
        }
        return $this->_options;
    }
}
