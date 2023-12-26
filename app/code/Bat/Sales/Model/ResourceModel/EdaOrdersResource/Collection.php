<?php

namespace Bat\Sales\Model\ResourceModel\EdaOrdersResource;

use Bat\Sales\Model\ResourceModel\EdaOrdersResource as EdaPendingOrdersResource;
use Bat\Sales\Model\EdaOrders;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class Collection
 *
 * Eda Pending Orders collection
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
        $this->_init(EdaOrders::class, EdaPendingOrdersResource::class);
    }
}
