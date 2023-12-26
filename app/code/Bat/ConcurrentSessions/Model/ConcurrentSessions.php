<?php

namespace Bat\ConcurrentSessions\Model;

use Magento\Framework\Model\AbstractModel;

class ConcurrentSessions extends AbstractModel
{

    /**
     * Price Master
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\ConcurrentSessions\Model\ResourceModel\ConcurrentSessions::class);
    }
}
