<?php

namespace Bat\Customer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class ChangeAddress
 * Define baturl Table
 */
class ChangeAddress extends AbstractDb
{
    private const TABLE_NAME = 'bat_url';
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
