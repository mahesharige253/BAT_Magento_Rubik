<?php

namespace Bat\Rma\Model\ResourceModel\ZreResource;

use Bat\Rma\Model\ResourceModel\ZreResource as ZreOrderResource;
use Bat\Rma\Model\ZreModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class ZreCollection
 *
 * Zre Order collection
 */
class ZreCollection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ZreModel::class, ZreOrderResource::class);
    }
}
