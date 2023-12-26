<?php

namespace Bat\CatalogRestApi\Model\ResourceModel\PriceMaster;

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
            \Bat\CatalogRestApi\Model\PriceMaster::class,
            \Bat\CatalogRestApi\Model\ResourceModel\PriceMaster::class
        );
    }
}
