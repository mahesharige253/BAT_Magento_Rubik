<?php

namespace Bat\Information\Block\Adminhtml\Information\EditOrderManual;

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
        $this->setId('informationform_create_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Order Manual'));
    } //end _construct()

    /**
     * BeforetoHtml function

     * @return WidgetTabs

     * @throws \Magento\Framework\Exception\LocalizedException
     */

    protected function _beforeToHtml()
    {
        $this->addTab(
            'informationform_create_tabs',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()->createBlock(
                    \Bat\Information\Block\Adminhtml\Information\EditOrderManual\Tab\Info::class
                )->toHtml(),
                'active' => true,
            ]
        );
        return parent::_beforeToHtml();
    } //end _beforeToHtml()
} //end class
