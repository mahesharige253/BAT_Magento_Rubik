<?php

namespace Bat\CatalogRestApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PriceMaster extends AbstractDb
{

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bat_price_master', 'id');
    }
}
