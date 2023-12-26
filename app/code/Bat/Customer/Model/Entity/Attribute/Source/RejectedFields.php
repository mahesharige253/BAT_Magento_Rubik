<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class RejectedFields extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Low quality/Wrong image'), 'value' => 'low_qualitywrong_image'],
                ['label' => __('Address not matching'), 'value' => 'address_not_matching'],
            ];
        }
        return $this->_options;
    }
}
