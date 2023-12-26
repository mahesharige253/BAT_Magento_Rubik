<?php

namespace Bat\Customer\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Customer\Model\ResourceModel\SigunguCodeResource;

/**
 * @class SigunguCode
 *
 * Initiate object for SigunguCode
 */
class SigunguCode extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SigunguCodeResource::class);
        parent::_construct();
    }
}
