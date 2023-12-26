<?php

namespace Bat\NewProduct\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class NewProductResource
 * Define New Products Table
 */
class NewProductResource extends AbstractDb
{
    private const TABLE_NAME = 'bat_new_recommended_products';
    private const PRIMARY_KEY = 'entity_id';

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}
