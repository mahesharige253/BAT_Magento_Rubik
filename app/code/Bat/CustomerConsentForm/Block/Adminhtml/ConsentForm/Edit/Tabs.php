<?php

namespace Bat\CustomerConsentForm\Block\Adminhtml\ConsentForm\Edit;

use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{
    /**
     * Intialize construct
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customerconsentform_create_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Content Form Information'));
    } //end _construct()

    /**
     * BeforetoHtml function

     * @return WidgetTabs

     * @throws \Magento\Framework\Exception\LocalizedException
     */

    protected function _beforeToHtml()
    {
        $this->addTab(
            'customerconsentform_create_tabs',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()->createBlock(
                    \Bat\CustomerConsentForm\Block\Adminhtml\ConsentForm\Edit\Tab\Info::class
                )->toHtml(),
                'active' => true,
            ]
        );
        return parent::_beforeToHtml();
    } //end _beforeToHtml()
} //end class
