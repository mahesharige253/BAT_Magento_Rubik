<?php

namespace Bat\Sales\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class EdaOrdersResource
 * Define Eda Pending orders Table
 */
class EdaOrdersResource extends AbstractDb
{
    private const TABLE_NAME = 'bat_eda_pending_orders';
    private const PRIMARY_KEY = 'entity_id';

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}
