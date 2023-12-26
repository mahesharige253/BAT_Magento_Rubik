<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  ProductList
 * @package   Bat_CatalogGraphQl
 * @author    Bat <bat@ample.com>
 * @copyright 2023 Your Name or Company Name
 * @license   Bat http://bat.com/
 * @version   SVN: $Id$
 * @link      Bat
 */

namespace Bat\CatalogGraphQl\Plugin\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar as Productdata;

/**
 * @class ToolBar
 *
 * Display Sorting Option
 */
class Toolbar
{
    /**
     * Around set collection for created_at sort option
     *
     * @param Toolbar $subject
     * @param Closure $proceed
     * @param Array $collection
     */
    public function aroundSetCollection(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed,
        $collection
    ) {
        $currentOrder = $subject->getCurrentOrder();
        if ($currentOrder == "latest") {
            $dir = $subject->getCurrentDirection();
            $subject->getCollection()->setOrder('bat_created_at', 'desc');
        }
        return $proceed($collection);
    }
}
