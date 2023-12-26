<?php

namespace Bat\AccountClosure\Model\ResourceModel\Closure;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bat\AccountClosure\Model\ResourceModel\Closure as ResourceClosure;
use Bat\AccountClosure\Model\Closure as ModelClosure;

class Collection extends AbstractCollection
{
    /**
     * @var $_idFieldName
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Collection construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ModelClosure::class, ResourceClosure::class);
    }
}
