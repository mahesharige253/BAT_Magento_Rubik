<?php

namespace Bat\VirtualBank\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class BankResource
 * Define bank Table
 */
class BankResource extends AbstractDb
{
    private const TABLE_NAME = 'bat_virtual_bank';
    private const PRIMARY_KEY = 'bank_id';

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
