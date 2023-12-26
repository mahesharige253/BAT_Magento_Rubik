<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items;

use Magento\Catalog\Model\Product;

/**
 * Admin RMA create order grid block
 *
 * @api
 * @since 100.0.2
 */
class NewItemsGrid extends \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Grid
{

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->removeColumn('reason');
        $this->removeColumn('condition');
        $this->removeColumn('resolution');
        $this->removeColumn('add_details_link');
        return $this;
    }
}
