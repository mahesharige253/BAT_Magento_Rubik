<?php

namespace Bat\VirtualBank\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\VirtualBank\Model\ResourceModel\AccountResource;

/**
 * @class AccountModel
 *
 * Initiate object for Bank Accounts
 */
class AccountModel extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AccountResource::class);
        parent::_construct();
    }
}
