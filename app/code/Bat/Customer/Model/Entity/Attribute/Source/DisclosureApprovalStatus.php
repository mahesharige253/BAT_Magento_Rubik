<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class DisclosureApprovalStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Please Select'), 'value' => ''],
                ['label' => __('Under Review'), 'value' => 1],
                ['label' => __('Approved'), 'value' => 2],
                ['label' => __('Rejected'), 'value' => 3],
                ['label' => __('Account Terminated'), 'value' => 4]
            ];
        }
        return $this->_options;
    }
}
