<?php

namespace Bat\Cms\Model\ResourceModel\CmsPageVersion;

use Bat\Cms\Model\ResourceModel\CmsPageVersion as PageVersionResource;
use Bat\Cms\Model\CmsPageVersionModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @class Collection
 * Cms Page Previous version collection
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
        $this->_init(CmsPageVersionModel::class, PageVersionResource::class);
    }
}
