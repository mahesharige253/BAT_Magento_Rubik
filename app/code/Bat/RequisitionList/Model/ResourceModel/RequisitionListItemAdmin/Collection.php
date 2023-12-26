<?php
namespace Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bat\RequisitionList\Model\RequisitionListItemAdmin;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin as RequisitionListItemAdminResource;

/**
 * RequisitionListItemAdmin Resource Model Collection
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
            RequisitionListItemAdmin::class,
            RequisitionListItemAdminResource::class
        );
    }
}
