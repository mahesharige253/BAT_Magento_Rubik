<?php
namespace Bat\AccountClosure\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\AccountClosure\Model\ResourceModel\AccountClosureProductReturn as AccountClosureProductReturnResouceModel;

/**
 * RequisitionListAdmin Model
 *
 */
class AccountClosureProductReturn extends AbstractModel
{
    
    /**
     * RequisitionListAdmin
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AccountClosureProductReturnResouceModel::class);
    }

    /**
     * Return products ids
     *
     * @param int $accountclosureId
     * @return array
     */
    public function getProducts($accountclosureId)
    {
        $tbl = $this->getResource()->getTable('account_closure_product_return');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['product_id']
         )
        ->where(
            "customer_id = ?",
            $accountclosureId
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }

    /**
     * Get qty
     *
     * @param int $accountclosureId
     * @param int $productId
     * @return array
     */
    public function getQty($accountclosureId, $productId)
    {
        $tbl = $this->getResource()->getTable('account_closure_product_return');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['qty']
         )
        ->where(
            "customer_id = ?",
            $accountclosureId
        )->where(
            "product_id = ?",
            $productId
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }

    /**
     * Get ProductReturned
     *
     * @param int $accountclosureId
     * @param int $productId
     * @return array
     */
    public function getProductReturned($accountclosureId, $productId)
    {
        $tbl = $this->getResource()->getTable('account_closure_product_return');
         $select = $this->getResource()->getConnection()->select()->from(
             $tbl,
             ['product_returned']
         )
        ->where(
            "customer_id = ?",
            $accountclosureId
        )->where(
            "product_id = ?",
            $productId
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }

    /**
     * Get LoadbycustomerId
     *
     * @param int $customerId
     * @return array
     */
    public function loadByCustomerId($customerId)
    {
        $tbl = $this->getResource()->getTable("account_closure_product_return");
        $select = $this->getResource()->getConnection()->select()->from(
            $tbl,
            '*'
        )
        ->where(
            "customer_id = ?",
            $customerId
        );
        return $this->getResource()->getConnection()->fetchAll($select);
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
        $tbl = $this->getResource()->getTable("account_closure_product_return");
        $select = $this->getResource()->getConnection()->select()->from(
            $tbl,
            ['id']
        )
            ->where(
                'customer_id = ?',
                (int)$entityId
            )
            ->where(
                'product_id not IN (?)',
                $products
            );
        return $this->getResource()->getConnection()->fetchCol($select);
    }

    /**
     * Get Product Return Qty
     *
     * @param int $customerId
     * @param int $productId
     * @param int $prodRtnQty
     */
    public function updateProductReturn($customerId, $productId, $prodRtnQty)
    {
        $tableName = $this->getResource()->getTable("account_closure_product_return");
        $sql = "Update " . $tableName . " Set product_returned = ".$prodRtnQty." 
                where customer_id = ".$customerId." AND product_id =".$productId;
        $this->getResource()->getConnection()->query($sql);
    }

    /**
     * Get Product Return Qty
     *
     * @param int $customerId
     * @param int $productId
     * @param int $prodRtnQty
     */
    public function checkProductReturnData($customerId)
    {
        $tableName = $this->getResource()->getTable("account_closure_product_return");
        $select = $this->getResource()->getConnection()->select()->from(
            $tableName,
            ['id']
        )
            ->where(
                'customer_id = ?',
                $customerId
            );
        $existData = $this->getResource()->getConnection()->fetchCol($select);
        if(count($existData) > 0) {
            $sql = "DELETE FROM " . $tableName . " WHERE customer_id = ".$customerId;
            $this->getResource()->getConnection()->query($sql);
        }
    }


    /**
     * Get Product Return Qty
     *
     * @param int $customerId
     * @param int $orderId
     */
    public function updateReturnOrder($customerId, $orderId)
    {
        $tableName = $this->getResource()->getTable("account_closure_product_return");
        $sql = "Update " . $tableName . " Set returnOrderId = ".$orderId." where customer_id = ".$customerId;
        $this->getResource()->getConnection()->query($sql);
    }

    /**
     * Get Product Return RMA Qty
     *
     * @param int $orderId
     */
    public function getReturnData($orderId)
    {
        $tbl = $this->getResource()->getTable("sales_order");
        $select = $this->getResource()->getConnection()->select()->from(
            $tbl,
            ['entity_id']
        )
            ->where(
                'increment_id = ?',
                $orderId
            );
        return $this->getResource()->getConnection()->fetchCol($select);
    }
}
