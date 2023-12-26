<?php

namespace Navigate\BannerSlider\Ui\Component\Listing\Columns\Bannerslider;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Function toOptionArray.
     */
    public function toOptionArray()
    {
        $options   = [];
        $options[] = [
            'label' => 'Enabled',
            'value' => 'Enabled',
        ];
        $options[] = [
            'label' => 'Disabled',
            'value' => 'Disabled',
        ];
        return $options;
    }//end toOptionArray()
}//end class
