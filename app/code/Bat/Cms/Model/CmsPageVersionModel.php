<?php

namespace Bat\Cms\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Cms\Model\ResourceModel\CmsPageVersion as PageVersionResource;

/**
 * @class CmsPageVersionModel
 * Initiate object for Cms Page Version
 */
class CmsPageVersionModel extends AbstractModel
{
    /**
     * Object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(PageVersionResource::class);
        parent::_construct();
    }
}
