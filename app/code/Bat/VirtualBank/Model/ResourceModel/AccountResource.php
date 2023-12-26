<?php

namespace Bat\VirtualBank\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class AccountResource
 * Define Bank Accounts Table
 */
class AccountResource extends AbstractDb
{
    private const TABLE_NAME = 'bat_vba_master';
    private const PRIMARY_KEY = 'vba_id';

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
