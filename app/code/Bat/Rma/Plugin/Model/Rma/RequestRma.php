<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\Rma\Plugin\Model\Rma;

use Magento\Framework\App\ResourceConnection;

/**
 * @class RequestRma
 *
 * Update order type status in sales_order table
 */
class RequestRma
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * After plugin to update order_type column value while requested for return
     *
     * @param RequestRma $subject
     * @param Array $result
     */
    public function afterExecute(
        \Magento\RmaGraphQl\Model\Rma\RequestRma $subject,
        $result
    ) {
        if ($result->getOrderId() !='') {
            $connection  = $this->resource->getConnection();
            $orderId = $result->getOrderId();
            $data = ["order_type" => __("Return Order")];
            $where = ['entity_id = ?' => (int)$orderId];
            $tableName = $connection->getTableName("sales_order");
            $connection->update($tableName, $data, $where);
        }
        return $result;
    }
}
