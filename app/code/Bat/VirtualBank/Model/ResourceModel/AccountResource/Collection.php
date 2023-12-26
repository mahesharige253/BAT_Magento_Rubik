<?php

namespace Bat\VirtualBank\Model\ResourceModel\AccountResource;

use Bat\VirtualBank\Model\ResourceModel\AccountResource as VirtualAccountResource;
use Bat\VirtualBank\Model\AccountModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class Collection
 *
 * Bank Accounts collection
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
        $this->_init(AccountModel::class, VirtualAccountResource::class);
    }
}
