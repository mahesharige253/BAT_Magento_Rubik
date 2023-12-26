<?php

namespace Bat\Discount\Block\Adminhtml\Promo\Widget\Chooser;

class Sku extends \Magento\CatalogRule\Block\Adminhtml\Promo\Widget\Chooser\Sku
{
    /**
     * Get catalog product resource collection instance
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getCpCollectionInstance()
    {
        if (!$this->_cpCollectionInstance) {
            $this->_cpCollectionInstance = $this->_cpCollection->create()->addAttributeToFilter('status',1);
        }
        return $this->_cpCollectionInstance;
    }
}
