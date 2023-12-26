<?php
namespace Bat\BulkOrder\Model\ResourceModel\BulkOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bat\BulkOrder\Model\BulkOrder;
use Bat\BulkOrder\Model\ResourceModel\BulkOrder as BulkOrderResource;

/**
 * BulkOrder Resource Model Collection
 *
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            BulkOrder::class,
            BulkOrderResource::class
        );
    }
}
