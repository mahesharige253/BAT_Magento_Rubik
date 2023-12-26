<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class DisclosureRejectedFields extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Bank Account Card'), 'value' => 'bank_account_card']
            ];
        }
        return $this->_options;
    }
}
