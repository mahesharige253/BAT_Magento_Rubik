<?php
namespace Bat\RequisitionList\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * RequisitionListAdmin Model
 *
 */
class RequisitionListAdmin extends AbstractModel
{
    
    /**
     * RequisitionListAdmin
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin::class);
    }

    /**
     * Get Rl Type Data
     *
     * @param int $rlTypeData
     * @return array
     */
    public function getRlTypeData($rlTypeData)
    {
        $tbl = $this->getResource()->getTable('requisition_list_admin');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['entity_id', 'rl_type', 'seasonal_percentage']
         )
        ->where(
            "rl_type != ?",
            "other"
        )
        ->where(
            "rl_type = ?",
            $rlTypeData
        );
        return $this->getResource()->getConnection()->fetchAll($select);
    }
}
