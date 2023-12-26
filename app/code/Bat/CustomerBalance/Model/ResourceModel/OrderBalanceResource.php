<?php

namespace Bat\CustomerBalance\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class OrderBalanceResource
 * Define Bat Order balance Table
 */
class OrderBalanceResource extends AbstractDb
{
    private const TABLE_NAME = 'bat_orderbalance';
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
