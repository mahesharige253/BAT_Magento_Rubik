<?php

namespace Bat\CustomerConsentForm\Block\Adminhtml\ConsentForm\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\App\RequestInterface;

class Info extends Generic implements TabInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $_coreSession;

    /**
     * @var Config
     */
    private $_wysiwygConfig;

      /**
     * @var RequestInterface
     */
    private $requestInterface;

    /**
     * Info constructor.
     *
     * @param Context                                            $context
     * @param Registry                                           $registry
     * @param FormFactory                                        $formFactory
     * @param SessionManagerInterface                            $coreSession
     * @param Config                                             $wysiwygConfig
     * @param array                                              $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        SessionManagerInterface $coreSession,
        Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_coreSession = $coreSession;
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

     /**
      * Prepareform function
      */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('customerconsentform');
        $form = $this->_formFactory->create();
        $val = '';
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
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'comment' => __('Title'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'linktitle',
            'text',
            [
                'name' => 'linktitle',
                'label' => __('PopUp Title'),
                'comment' => __('PopUp Title'),
                'note' => 'Display popup title of the Link.',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'consent_type',
            'multiselect',
            [
                'name' => 'consent_type',
                'label' => __('Consent Type'),
                'comment' => __('Consent Type'),
                'note' => 'Display At which page this consent will be used.',
                'values' => [
                    [
                        'value' => 'Registration',
                        'label' => 'Registration Page',
                    ],
                    [
                        'value' => 'Checkout',
                        'label' => 'Checkout Page',
                    ],
                    [
                        'value' => 'Cart',
                        'label' => 'Cart Page',
                    ],
                    [
                        'value' => 'Account',
                        'label' => 'Account Information Page',
                    ],
                    [
                        'value' => 'Deactivation',
                        'label' => 'Deactivation account Page',
                    ],
                    [
                        'value' => 'Bulkorder_checkout',
                        'label' => 'Bulkorder checkout Page',
                    ],
                    [
                        'value' => 'Bulkorder_review',
                        'label' => 'Bulkorder review page',
                    ]
                ],
            ]
        );
        $val = $this->getRequest()->getParam('id');
        if(isset($val)){
            $fieldset->addField(
                'identifier',
                'text',
                [
                    'name' => 'identifier',
                    'label' => __('Identifier'),
                    'comment' => __('Identifier'),
                    'required' => true,
                    'disabled' => true
                ]
            );
        }
       else{
            $fieldset->addField(
                'identifier',
                'text',
                [
                    'name' => 'identifier',
                    'label' => __('Identifier'),
                    'comment' => __('Identifier'),
                    'required' => true,
                ]
            );
        }
    
        $fieldset->addField(
            'enable_link',
            'select',
            [
                'name' => 'enable_link',
                'label' => __('Link Status'),
                'comment' => __('Link Status'),
                'values' => [
                    [
                        'value' => 'Enabled',
                        'label' => 'Enabled',
                    ],
                    [
                        'value' => 'Disabled',
                        'label' => 'Disabled',
                    ],
                ],
            ]
        );

        $fieldset->addField(
            'consent_required',
            'select',
            [
                'name' => 'consent_required',
                'label' => __('Is Consent Required?'),
                'comment' => __('Is Consent Required?'),
                'values' => [
                    [
                        'value' => 'No',
                        'label' => 'No',
                    ],
                    [
                        'value' => 'Yes',
                        'label' => 'Yes',
                    ],
                ],
            ]
        );

        $fieldset->addField(
            "validation",
            "text",
            [
                "label"     => __("Validation message"),
                "class"     => "required-entry",
                "required"  => true,
                "name"      => "validation"
            ]
        );

        $fieldset->addField(
            'content',
            'editor',
            [
                'name' => 'content',
                'label' => __('Body'),
                'title' => __('Consent Content'),
                'rows' => '5',
                'cols' => '30',
                'wysiwyg' => true,
                'config' => $this->_wysiwygConfig->getConfig(),
                'required' => false
            ]
        );

        $fieldset->addField(
            'position',
            'text',
            [
                'name' => 'position',
                'label' => __('Position'),
                'comment' => __('Position'),
                'class' => 'validate-number',
                'note' => "<script type='text/javascript'>
require([
    'jquery'
], function ($) {
    'use strict';
    jQuery(document).ready(function(){
         function updatefeilds() {
          var consent_required = $('#consent_required').val();
         if(consent_required=='Yes')
         {
           $('#validation').addClass('required-entry');
           $('#validation').parent().parent().show();

         }else{
           $('#validation').removeClass('required-entry');
           $('#validation').parent().parent().hide();
        }
}
        $('#validation').change(function(){
            updatefeilds();
        });
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
    } //end _prepareForm()

    /**
     * GetTabLabel function

     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('ConsentForm');
    } //end getTabLabel()

    /**
     * GetTabTitle function

     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('ConsentForm');
    } //end getTabTitle()

    /**
     * CanshowTab function

     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    } //end canShowTab()

    /**
     * Ishidden Function

     * @return boolean
     */
    public function isHidden()
    {
        return false;
    } //end isHidden()
} //end class
