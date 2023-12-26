<?php

namespace Bat\VirtualBank\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\VirtualBank\Model\ResourceModel\BankResource;

/**
 * @class BankModel
 * Initiate object for Banks
 */
class BankModel extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(BankResource::class);
        parent::_construct();
    }
}
