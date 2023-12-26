<?php

namespace Bat\ConcurrentSessions\Model\ResourceModel\ConcurrentSessions;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * Initialize resource collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Bat\ConcurrentSessions\Model\ConcurrentSessions::class,
            \Bat\ConcurrentSessions\Model\ResourceModel\ConcurrentSessions::class
        );
    }
}
