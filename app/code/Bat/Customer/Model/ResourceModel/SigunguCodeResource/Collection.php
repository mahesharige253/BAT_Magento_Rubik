<?php

namespace Bat\Customer\Model\ResourceModel\SigunguCodeResource;

use Bat\Customer\Model\ResourceModel\SigunguCodeResource as BatSigunguCodeResource;
use Bat\Customer\Model\SigunguCode;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class Collection
 *
 * BAT Sigungu Code Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SigunguCode::class, BatSigunguCodeResource::class);
    }
}
