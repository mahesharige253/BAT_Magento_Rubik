<?php

namespace Bat\CatalogGraphQl\Plugin\Model;

use Magento\Store\Model\StoreManagerInterface;

class Config
{
    /**
     * Added new option in sort by for displaying product by created_at
     *
     * @param Config $catalogConfig
     * @param Array $options
     */
    public function afterGetAttributeUsedForSortByArray(\Magento\Catalog\Model\Config $catalogConfig, $options)
    {
        $customOption['bat_created_at'] = __("Latest Product");
        $options = array_merge($options, $customOption);
        return $options;
    }
}
