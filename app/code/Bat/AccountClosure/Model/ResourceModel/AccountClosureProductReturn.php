<?php
namespace Bat\AccountClosure\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * RequisitionListAdmin Resource Model
 *
 */
class AccountClosureProductReturn extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('account_closure_product_return', 'id');
    }
}
