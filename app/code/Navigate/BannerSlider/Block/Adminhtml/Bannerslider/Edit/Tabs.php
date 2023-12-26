<?php

namespace Navigate\BannerSlider\Block\Adminhtml\Bannerslider\Edit;

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
        $this->setId('bannerslider_create_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Banner Information'));
    } //end _construct()

    /**
     * BeforetoHtml function

     * @return WidgetTabs
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'bannerslider_create_tabs',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->getLayout()->
                createBlock(\Navigate\BannerSlider\Block\Adminhtml\Bannerslider\Edit\Tab\Info::class)->toHtml(),
                'active' => true,
            ]
        );
        return parent::_beforeToHtml();
    } //end _beforeToHtml()
} //end class
