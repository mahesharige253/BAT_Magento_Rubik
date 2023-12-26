<?php
namespace Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bat\RequisitionList\Model\RequisitionListAdmin;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin as RequisitionListAdminResource;

/**
 * RequisitionListAdmin Resource Model Collection
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
            RequisitionListAdmin::class,
            RequisitionListAdminResource::class
        );
    }
}
