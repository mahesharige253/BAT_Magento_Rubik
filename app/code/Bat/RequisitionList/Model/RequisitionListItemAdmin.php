<?php
namespace Bat\RequisitionList\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * RequisitionListItemAdmin Model
 *
 */
class RequisitionListItemAdmin extends AbstractModel
{

    /**
     * RequisitionListItemAdmin
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin::class);
    }
    
    /**
     * Return products ids
     *
     * @param int $requisitionListId
     * @return array
     */
    public function getProducts($requisitionListId)
    {
        $tbl = $this->getResource()->getTable('requisition_list_item_admin');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['product_id']
         )
        ->where(
            "requisition_list_id = ?",
            $requisitionListId
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }

    /**
     * Get qty
     *
     * @param int $requisitionListId
     * @param int $productId
     * @return array
     */
    public function getQty($requisitionListId, $productId)
    {
        $tbl = $this->getResource()->getTable('requisition_list_item_admin');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['qty']
         )
        ->where(
            "requisition_list_id = ?",
            $requisitionListId
        )->where(
            "product_id = ?",
            $productId
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }

    /**
     * Get Requisition List Item Id
     *
     * @param int $requisitionListId
     * @param int $productId
     * @return array
     */
    public function getRequisitionListItemId($requisitionListId, $productId)
    {
        $tbl = $this->getResource()->getTable('requisition_list_item_admin');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['item_id']
         )
        ->where(
            "requisition_list_id = ?",
            $requisitionListId
        )->where(
            "product_id = ?",
            $productId
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }

    /**
     * Get Products By Entity Id
     *
     * @param int $entityId
     * @param array $products
     * @return array
     */
    public function getProductsByEntityId($entityId, $products)
    {
        $tbl = $this->getResource()->getTable("requisition_list_item_admin");
        $select = $this->getResource()->getConnection()->select()->from(
            $tbl,
            ['item_id']
        )
            ->where(
                'requisition_list_id = ?',
                (int)$entityId
            )
            ->where(
                'product_id not IN (?)',
                $products
            );
        return $this->getResource()->getConnection()->fetchCol($select);
    }
}
