<?php

namespace Navigate\BannerSlider\Model;

class Bannerslider extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Initialize construct Navigate\BannerSlider\Model\Bannerslider
     */
    public function _construct()
    {
        $this->_init(\Navigate\BannerSlider\Model\ResourceModel\Bannerslider::class);
    }//end _construct()
}//end class
