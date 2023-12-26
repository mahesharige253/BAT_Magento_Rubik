<?php

namespace Bat\CustomerConsentForm\Ui\Component\Listing\Columns\ConsentForm;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{

    /**
     * Options array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = [
            'label' => 'Enabled',
            'value' => 'Enabled',
        ];
        $options[] = [
            'label' => 'Disabled',
            'value' => 'Disabled',
        ];
        return $options;
    } //end toOptionArray()
} //end class
