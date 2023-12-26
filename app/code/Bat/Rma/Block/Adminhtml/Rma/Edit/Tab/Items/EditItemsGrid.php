<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

/**
 * Admin RMA create order grid block
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class EditItemsGrid extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid
{
    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumnAfter(
            'fresh_requested',
            [
                'header' => __('Fresh Requested'),
                'type' => 'text',
                'index' => 'fresh_requested',
                'sortable' => false,
                'escape' => true,
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ],
            'qty_requested'
        );

        $this->addColumnAfter(
            'old_requested',
            [
                'header' => __('Old Requested'),
                'type' => 'text',
                'index' => 'old_requested',
                'sortable' => false,
                'escape' => true,
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ],
            'fresh_requested'
        );

        $this->addColumnAfter(
            'damage_requested',
            [
                'header' => __('Damage Requested'),
                'type' => 'text',
                'index' => 'damage_requested',
                'sortable' => false,
                'escape' => true,
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ],
            'old_requested'
        );
        parent::_prepareColumns();
        $this->removeColumn('reason');
        $this->removeColumn('condition');
        $this->removeColumn('resolution');
        $this->removeColumn('action');
        return $this;
    }
}
