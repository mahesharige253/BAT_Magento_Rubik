<?php
namespace Bat\AccountClosure\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * RequisitionListAdmin Resource Model
 *
 */
class AccountClosureAdmin extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('customer_entity', 'entity_id');
    }
}
