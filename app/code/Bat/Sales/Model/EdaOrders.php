<?php

namespace Bat\Sales\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;

/**
 * @class EdaOrders
 *
 * Initiate object for EDA Pending Orders
 */
class EdaOrders extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(EdaOrdersResource::class);
        parent::_construct();
    }
}
