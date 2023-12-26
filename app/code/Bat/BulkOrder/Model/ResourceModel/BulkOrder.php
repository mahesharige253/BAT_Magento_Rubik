<?php
namespace Bat\BulkOrder\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * BulkOrder Resource Model
 *
 */
class BulkOrder extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('bat_bulkorder', 'id');
    }
}
