<?php

namespace Bat\BestSellers\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BestSellers extends AbstractDb
{

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bat_bestseller', 'id');
    }
}
