<?php

namespace Bat\Attributes\Model\Source;

class EdaCustomerPaymentTerm extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Select Payment Term'), 'value' => ''],
                ['label' => __('AFTER 7 DAYS'), 'value' => '007'],
                ['label' => __('AFTER 14 DAYS'), 'value' => '014'],
                ['label' => __('AFTER 28 DAYS'), 'value' => '028'],
                ['label' => __('AFTER 30 DAYS'), 'value' => '030'],
                ['label' => __('AFTER 45 DAYS'), 'value' => '045'],
                ['label' => __('AFTER 60 DAYS'), 'value' => '060'],
                ['label' => __('Payable Immediately Due Net'), 'value' => 'COD']
            ];
        }
        return $this->_options;
    }
}
