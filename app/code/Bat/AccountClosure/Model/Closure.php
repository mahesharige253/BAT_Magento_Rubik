<?php

namespace Bat\AccountClosure\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\AccountClosure\Model\ResourceModel\Closure as ResourceClosure;

class Closure extends AbstractModel
{
    /**
     * Closure Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceClosure::class);
    } 

    /**
     * Get EnityId by CustomerId
     *
     * @param int $customerId
     */
    public function getIdbyCustomerId($customerId)
    {
        $tbl = $this->getResource()->getTable("bat_account_closure");
        $select = $this->getResource()->getConnection()->select()->from(
            $tbl,
            ['entity_id']
        )
            ->where(
                'customer_id = ?',
                $customerId
            );
        return $this->getResource()->getConnection()->fetchCol($select);
    }
}
