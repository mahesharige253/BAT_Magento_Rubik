<?php

namespace Bat\Rma\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Rma\Model\ResourceModel\ZreResource;

/**
 * @class ZreModel
 * Initiate object for Zre orders
 */
class ZreModel extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ZreResource::class);
        parent::_construct();
    }
}
