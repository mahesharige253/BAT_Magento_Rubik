<?php

namespace Bat\Customer\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Customer\Model\ResourceModel\EdaCustomersResource;

/**
 * @class EdaCustomers
 *
 * Initiate object for EDA Pending Customers
 */
class EdaCustomers extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(EdaCustomersResource::class);
        parent::_construct();
    }
}
