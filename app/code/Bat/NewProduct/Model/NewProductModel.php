<?php

namespace Bat\NewProduct\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\NewProduct\Model\ResourceModel\NewProductResource;

/**
 * @class NewProductModel
 *
 * Initiate object for New Products
 */
class NewProductModel extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(NewProductResource::class);
        parent::_construct();
    }

    /**
     * Return new products
     *
     * @return array
     */
    public function getProducts()
    {
        $tbl = $this->getResource()->getTable('bat_new_recommended_products');
        $select = $this->getResource()->getConnection()->select()->from(
            $tbl,
            ['product_id']
        );
        return $this->getResource()->getConnection()->fetchCol($select);
    }
}
