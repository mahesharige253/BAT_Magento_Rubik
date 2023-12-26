<?php
namespace Bat\RequisitionList\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * RequisitionListAdmin Resource Model
 *
 */
class RequisitionListAdmin extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('requisition_list_admin', 'entity_id');
    }
}
