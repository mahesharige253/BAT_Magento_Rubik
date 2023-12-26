<?php

namespace Bat\Cms\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @class CmsPageVersion
 * Define Main Table
 */
class CmsPageVersion extends AbstractDb
{
    private const TABLE_NAME = 'bat_cms_page_version';
    private const PRIMARY_KEY = 'page_id';

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
