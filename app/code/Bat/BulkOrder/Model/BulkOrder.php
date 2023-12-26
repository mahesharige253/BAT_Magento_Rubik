<?php
namespace Bat\BulkOrder\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * BulkOrder Model
 *
 */
class BulkOrder extends AbstractModel
{

    /**
     * BulkOrder
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\BulkOrder\Model\ResourceModel\BulkOrder::class);
    }
}
