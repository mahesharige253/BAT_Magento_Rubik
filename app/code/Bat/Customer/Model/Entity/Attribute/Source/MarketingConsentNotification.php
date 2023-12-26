<?php

namespace Bat\Customer\Model\Entity\Attribute\Source;

class MarketingConsentNotification implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Years')],
            ['value' => 1, 'label' => __('Months')]
        ];
    }
}