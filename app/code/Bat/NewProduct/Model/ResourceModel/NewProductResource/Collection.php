<?php

namespace Bat\NewProduct\Model\ResourceModel\NewProductResource;

use Bat\NewProduct\Model\ResourceModel\NewProductResource as NewProductsResource;
use Bat\NewProduct\Model\NewProductModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class Collection
 *
 * New Products collection
 */
class Collection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(NewProductModel::class, NewProductsResource::class);
    }
}
