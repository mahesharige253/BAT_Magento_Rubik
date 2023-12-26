<?php

namespace Bat\Rma\Model\ResourceModel\IroResource;

use Bat\Rma\Model\ResourceModel\IroResource as IroOrderResource;
use Bat\Rma\Model\IroModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class IroCollection
 *
 * Iro Order collection
 */
class IroCollection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(IroModel::class, IroOrderResource::class);
    }
}
