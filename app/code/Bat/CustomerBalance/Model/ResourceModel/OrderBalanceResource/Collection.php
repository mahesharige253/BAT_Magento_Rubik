<?php

namespace Bat\CustomerBalance\Model\ResourceModel\OrderBalanceResource;

use Bat\CustomerBalance\Model\ResourceModel\OrderBalanceResource as BatOrderBalanceResource;
use Bat\CustomerBalance\Model\OrderBalanceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class OrderBalanceCollection
 *
 * Order Balance Collection
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
        $this->_init(OrderBalanceModel::class, BatOrderBalanceResource::class);
    }
}
