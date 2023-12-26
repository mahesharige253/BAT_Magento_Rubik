<?php

namespace Bat\ConcurrentSessions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ConcurrentSessions extends AbstractDb
{

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bat_concurrent_sessions', 'id');
    }
}
