<?php

namespace Bat\Information\Ui\Component\Listing\Columns\InformationForm;

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
