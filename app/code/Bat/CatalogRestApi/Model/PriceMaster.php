<?php

namespace Bat\CatalogRestApi\Model;

use Magento\Framework\Model\AbstractModel;

class PriceMaster extends AbstractModel
{

    /**
     * Price Master
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\CatalogRestApi\Model\ResourceModel\PriceMaster::class);
    }
}
