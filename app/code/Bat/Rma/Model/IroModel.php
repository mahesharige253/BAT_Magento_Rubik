<?php

namespace Bat\Rma\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Rma\Model\ResourceModel\IroResource;

/**
 * @class IroModel
 * Initiate object for Iro orders
 */
class IroModel extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(IroResource::class);
        parent::_construct();
    }
}
