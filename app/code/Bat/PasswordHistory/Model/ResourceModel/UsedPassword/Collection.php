<?php
namespace Bat\PasswordHistory\Model\ResourceModel\UsedPassword;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bat\PasswordHistory\Model\UsedPassword;
use Bat\PasswordHistory\Model\ResourceModel\UsedPassword as UsedPasswordResource;

class Collection extends AbstractCollection
{
    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(UsedPassword::class, UsedPasswordResource::class);
    }
}
