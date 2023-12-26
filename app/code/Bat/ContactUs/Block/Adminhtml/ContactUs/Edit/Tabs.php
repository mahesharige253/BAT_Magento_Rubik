<?php

namespace Bat\ContactUs\Block\Adminhtml\ContactUs\Edit;

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
        $this->setId('contactusform_create_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Contact Us'));
    } //end _construct()

    /**
     * BeforetoHtml function

     * @return WidgetTabs

     * @throws \Magento\Framework\Exception\LocalizedException
     */

    protected function _beforeToHtml()
    {
        $this->addTab(
            'contactusform_create_tabs',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()->createBlock(
                    \Bat\ContactUs\Block\Adminhtml\ContactUs\Edit\Tab\Info::class
                )->toHtml(),
                'active' => true,
            ]
        );
        return parent::_beforeToHtml();
    } //end _beforeToHtml()
} //end class
