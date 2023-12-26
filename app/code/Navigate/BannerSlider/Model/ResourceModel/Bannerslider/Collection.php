<?php

namespace Navigate\BannerSlider\Model\ResourceModel\Bannerslider;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var _idFieldName
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize construct

     * Initialize Bannerslider models
     */
    public function _construct()
    {
        $this->_init(
            \Navigate\BannerSlider\Model\Bannerslider::class,
            \Navigate\BannerSlider\Model\ResourceModel\Bannerslider::class
        );
    }//end _construct()
}//end class
