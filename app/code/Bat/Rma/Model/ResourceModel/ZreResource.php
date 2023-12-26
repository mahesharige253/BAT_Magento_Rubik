<?php

namespace Bat\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class ZreResource
 * Define Zre Orders Table
 */
class ZreResource extends AbstractDb
{
    private const TABLE_NAME = 'bat_zre_orders';
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
