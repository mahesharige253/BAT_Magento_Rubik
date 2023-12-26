<?php

namespace Navigate\BannerSlider\Model\ResourceModel;

class Bannerslider extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var _idFieldName
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize construct Navigate\BannerSlider\Model\ResourceModel\Bannerslider
     */
    public function _construct()
    {
        $this->_init('bannerslider', 'id');
    }//end _construct()
}//end class
