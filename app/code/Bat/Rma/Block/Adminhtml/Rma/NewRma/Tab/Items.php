<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\Rma\Block\Adminhtml\Rma\NewRma\Tab;

/**
 * Items Tab in Edit RMA form
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Items extends \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items
{
    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $htmlIdPrefix = 'rma_properties_';
        $form->setHtmlIdPrefix($htmlIdPrefix);

        $model = $this->_coreRegistry->registry('current_rma');

        $fieldset = $form->addFieldset('rma_item_fields', []);

        $fieldset->addField(
            'product_name',
            'text',
            ['label' => __('Product Name'), 'name' => 'product_name', 'required' => false]
        );

        $fieldset->addField(
            'product_sku',
            'text',
            ['label' => __('SKU'), 'name' => 'product_sku', 'required' => false]
        );

        //Renderer puts available quantity instead of order_item_id
        $fieldset->addField(
            'qty_ordered',
            'text',
            ['label' => __('Remaining Qty'), 'name' => 'qty_ordered', 'required' => false]
        );

        $fieldset->addField(
            'qty_requested',
            'text',
            [
                'label' => __('Requested Qty'),
                'name' => 'qty_requested',
                'required' => false,
                'class' => 'validate-greater-than-zero'
            ]
        );

        $fieldset->addField(
            'fresh_requested',
            'text',
            [
                'label' => __('Fresh Qty'),
                'name' => 'fresh_requested',
                'required' => false,
                'class' => 'validate-greater-than-zero fresh_requested'
            ]
        );

        $fieldset->addField(
            'old_requested',
            'text',
            [
                'label' => __('Old Qty'),
                'name' => 'old_requested',
                'required' => false,
                'class' => 'validate-greater-than-zero old_requested'
            ]
        );

        $fieldset->addField(
            'damage_requested',
            'text',
            [
                'label' => __('Damage Qty'),
                'name' => 'damage_requested',
                'required' => false,
                'class' => 'validate-greater-than-zero damage_requested'
            ]
        );

        $fieldset->addField(
            'delete_link',
            'label',
            ['label' => __('Delete'), 'name' => 'delete_link', 'required' => false]
        );

        $this->setForm($form);
        return $this;
    }
}
