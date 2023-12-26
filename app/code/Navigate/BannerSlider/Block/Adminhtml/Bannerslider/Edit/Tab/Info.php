<?php

namespace Navigate\BannerSlider\Block\Adminhtml\Bannerslider\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;

class Info extends Generic implements TabInterface
{
    /**
     * Info constructor.
     *
     * @param Context                                            $context
     * @param Registry                                           $registry
     * @param FormFactory                                        $formFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param array                                              $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        array $data = []
    ) {
        $this->_coreSession = $coreSession;
        parent::__construct($context, $registry, $formFactory, $data);
    }//end __construct()

    /**
     * Create form

     * @return Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('bannerslider');
        $form  = $this->_formFactory->create();

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information')]
        );
        if ($model->getId()) {
            $fieldset->addField(
                'id',
                'hidden',
                ['name' => 'id']
            );
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'name'    => 'status',
                'label'   => __('Status'),
                'comment' => __('Status'),
                'values'  => [
                    [
                        'value' => 'Enabled',
                        'label' => 'Enabled',
                    ]
                ],
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name'     => 'title',
                'label'    => __('Title'),
                'comment'  => __('Title'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'slidertitle',
            'text',
            [
                'name'    => 'slidertitle',
                'label'   => __('Slider Title'),
                'comment' => __('Slider Title'),
                'note'    => 'Display title on the image.',

            ]
        );

        $fieldset->addField(
            'banner_url_key',
            'text',
            [
                'name'    => 'banner_url_key',
                'label'   => __('Banner Url Key'),
                'comment' => __('Banner Url Key'),
            ]
        );

        $fieldset->addField(
            'enable_button',
            'select',
            [
                'name'    => 'enable_button',
                'label'   => __('Enable Button'),
                'comment' => __('Enable Button'),
                'values'  => [
                    [
                        'value' => '0',
                        'label' => 'No',
                    ],
                    [
                        'value' => '1',
                        'label' => 'Yes',
                    ],
                ],
            ]
        );

        $fieldset->addField(
            'buttontitle',
            'text',
            [
                'name'    => 'buttontitle',
                'label'   => __('Button Title'),
                'comment' => __('Button Title'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'url_key',
            'text',
            [
                'name'    => 'url_key',
                'label'   => __('Button Url Key'),
                'comment' => __('Button Url Key'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'open_new_tab',
            'select',
            [
                'name'    => 'open_new_tab',
                'label'   => __('Open Link In New Tab'),
                'comment' => __('Open Link In New Tab'),
                'default'=>'1',
                'value' => 1,
                'values'    => [
                                 ['label' => 'Yes', 'value' => 1],
                                 ['label' => 'No', 'value' => 0]
                               ]
            ]
        );

        $fieldset->addField(
            'imagename',
            'image',
            [
                'name'     => 'imagename',
                'label'    => __('Desktop Image'),
                'comment'  => __('Image'),
                'required' => true,
                'note'     => 'Maximum file size: 2 MB. Allowed file types: jpg,jpeg,png,svg,gif',
            ]
        );

        $fieldset->addField(
            'position',
            'text',
            [
                'name'    => 'position',
                'label'   => __('Position'),
                'comment' => __('Position'),
                'class'   => 'validate-number',
                'note'=>"<script type='text/javascript'>
require([
    'jquery'
], function ($) {
    'use strict';
    jQuery(document).ready(function(){

         function updatefeilds() {
          var enable_button = $('#enable_button').val();

         if(enable_button=='1')
         {

           $('#buttontitle').addClass('required-entry');
           $('#buttontitle').parent().parent().show();
           $('#text_position').parent().parent().show();
           $('#url_key').parent().parent().show();
           $('#url_key').addClass('required-entry');
           $('#open_new_tab').parent().parent().show();

         }else{

           $('#buttontitle').removeClass('required-entry');
           $('#buttontitle').parent().parent().hide();
           $('#text_position').parent().parent().hide();
           $('#url_key').parent().parent().hide();
           $('#url_key').removeClass('required-entry');
           $('#open_new_tab').parent().parent().hide();

        }
}
        $('#enable_button').change(function(){
            updatefeilds();
        });

      var pathname = window.location.pathname;
       if(pathname.includes('new')){
         $('#open_new_tab').val(1);
         $('#text_position').val('center');
       }
        setInterval(updatefeilds, 500);
});
});
</script>"
            ]
        );
        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }//end _prepareForm()

    /**
     * Get Label function

     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Bannerslider');
    }//end getTabLabel()

    /**
     * Get Tab Title function

     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('Bannerslider');
    }//end getTabTitle()

    /**
     * Get canshowtab function

     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }//end canShowTab()

    /**
     * Get isHidden function

     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }//end isHidden()
}//end class
