<?php

namespace Bat\CustomerBalance\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\CustomerBalance\Model\ResourceModel\OrderBalanceResource;

/**
 * @class OrderBalanceModel
 * Initiate object for Order Balance
 */
class OrderBalanceModel extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderBalanceResource::class);
        parent::_construct();
    }
}
