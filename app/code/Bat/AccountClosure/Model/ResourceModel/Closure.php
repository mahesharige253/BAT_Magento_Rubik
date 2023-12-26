<?php

namespace Bat\AccountClosure\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Closure extends AbstractDb
{
    public const TABLE_NAME = 'bat_account_closure';
    
    /**
     * Closure construct
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(static::TABLE_NAME, 'entity_id');
    }

}
