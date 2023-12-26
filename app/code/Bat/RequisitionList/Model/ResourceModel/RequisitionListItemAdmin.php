<?php
namespace Bat\RequisitionList\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * RequisitionListItemAdmin Resource Model
 *
 */
class RequisitionListItemAdmin extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('requisition_list_item_admin', 'item_id');
    }
}
