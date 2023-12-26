<?php
namespace Bat\AccountClosure\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * RequisitionListAdmin Model
 *
 */
class AccountClosureAdmin extends AbstractModel
{
    
    /**
     * RequisitionListAdmin
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\AccountClosure\Model\ResourceModel\AccountClosureAdmin::class);
    }
}
