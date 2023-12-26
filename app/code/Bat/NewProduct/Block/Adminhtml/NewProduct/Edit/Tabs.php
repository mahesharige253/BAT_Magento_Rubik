<?php

namespace Bat\NewProduct\Block\Adminhtml\NewProduct\Edit;

/**
 * Admin page left menu
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Set Tab
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('newproduct_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('New/Recommended Products'));
    }
}
