<?php

namespace Bat\ShipmentUpdate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class ChangeAddress
 * Define baturl Table
 */
class EdaOrderFailed extends AbstractDb
{
    private const TABLE_NAME = 'eda_failed_order';
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
