<?php

namespace Bat\VirtualBank\Model\ResourceModel\BankResource;

use Bat\VirtualBank\Model\ResourceModel\BankResource as VirtualBankResource;
use Bat\VirtualBank\Model\BankModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class BankCollection
 *
 * Banks collection
 */
class BankCollection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BankModel::class, VirtualBankResource::class);
    }
}
