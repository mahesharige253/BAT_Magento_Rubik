<?php

namespace Bat\BestSellers\Model\ResourceModel\BestSellers;

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
            \Bat\BestSellers\Model\BestSellers::class,
            \Bat\BestSellers\Model\ResourceModel\BestSellers::class
        );
    }
}
