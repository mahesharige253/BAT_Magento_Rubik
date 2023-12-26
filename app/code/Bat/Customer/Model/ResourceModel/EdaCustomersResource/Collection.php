<?php

namespace Bat\Customer\Model\ResourceModel\EdaCustomersResource;

use Bat\Customer\Model\ResourceModel\EdaCustomersResource as EdaPendingCustomersResource;
use Bat\Customer\Model\EdaCustomers;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class Collection
 *
 * Eda Pending Customers collection
 */
class Collection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(EdaCustomers::class, EdaPendingCustomersResource::class);
    }
}
