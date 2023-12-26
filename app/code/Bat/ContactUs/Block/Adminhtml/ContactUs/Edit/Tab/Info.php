<?php

namespace Bat\ContactUs\Block\Adminhtml\ContactUs\Edit\Tab;

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
        $model = $this->_coreRegistry->registry('contactusform');
        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information')]
        );
        if ($model) {
            if ($model->getId()) {
                $fieldset->addField(
                    'id',
                    'hidden',
                    ['name' => 'id']
                );
            }
        }
        $fieldset->addField(
            'page_title',
            'text',
            [
                'name' => 'page_title',
                'label' => __('Title'),
                'comment' => __('Title'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'bannerimage',
            'image',
            [
                'name' => 'bannerimage',
                'label' => __('Banner Image'),
                'comment' => __('Banner Image'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'operating_hours_label',
            'text',
            [
                'name' => 'operating_hours_label',
                'label' => __('Operating Hours Label'),
                'comment' => __('Operating Hours Label'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'operating_hours_value',
            'text',
            [
                'name' => 'operating_hours_value',
                'label' => __('Operating Hours Value'),
                'comment' => __('Operating Hours Value'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'operating_hours_value_two',
            'text',
            [
                'name' => 'operating_hours_value_two',
                'label' => __('Operating Hours Value 2'),
                'comment' => __('Operating Hours Value 2'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'contact_number',
            'text',
            [
                'name' => 'contact_number',
                'label' => __('Contact Number'),
                'comment' => __('Contact Number'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'company_name_label',
            'text',
            [
                'name' => 'company_name_label',
                'label' => __('Company Name Label'),
                'comment' => __('Company Name Label'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'company_name_value',
            'text',
            [
                'name' => 'company_name_value',
                'label' => __('Company Name Value'),
                'comment' => __('Company Name Value'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'company_address_label',
            'text',
            [
                'name' => 'company_address_label',
                'label' => __('Company Address Label'),
                'comment' => __('Company Address Label'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'company_address_value',
            'text',
            [
                'name' => 'company_address_value',
                'label' => __('Company Address Value'),
                'comment' => __('Company Address Value'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'business_license_label',
            'text',
            [
                'name' => 'business_license_label',
                'label' => __('Business License Label'),
                'comment' => __('Business License Label'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'business_license_value',
            'text',
            [
                'name' => 'business_license_value',
                'label' => __('Business License Value'),
                'comment' => __('Business License Value'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'representative_label',
            'text',
            [
                'name' => 'representative_label',
                'label' => __('Representative Label'),
                'comment' => __('Representative Label'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'representative_value',
            'text',
            [
                'name' => 'representative_value',
                'label' => __('Representative Value'),
                'comment' => __('Representative Value'),
                'required' => false,
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
        return __('contactusform');
    } //end getTabLabel()

    /**
     * GetTabTitle function

     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('contactusform');
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
