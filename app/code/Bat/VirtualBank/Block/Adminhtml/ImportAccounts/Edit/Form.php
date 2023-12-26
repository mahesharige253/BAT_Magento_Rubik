<?php

namespace Bat\VirtualBank\Block\Adminhtml\ImportAccounts\Edit;

use Bat\VirtualBank\Model\Source\Banks as BankOptionArray;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Bat\VirtualBank\Block\Adminhtml\SampleCsv\Renderer\Csvfile;

/**
 * Class Form
 * Import Vba Accounts Form
 */
class Form extends Generic
{
    /**
     * @var Config
     */
    private Config $_wysiwygConfig;

    /**
     * @var BankOptionArray
     */
    private BankOptionArray $bankOptionArray;

    /**
     * @var Csvfile
     */
    private $csvfile;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Config $wysiwygConfig
     * @param BankOptionArray $bankOptionArray
     * @param Csvfile $csvfile
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Config $wysiwygConfig,
        BankOptionArray $bankOptionArray,
        Csvfile $csvfile,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->bankOptionArray = $bankOptionArray;
        $this->csvfile = $csvfile;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return Form
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'enctype' => 'multipart/form-data',
                    'action' => $this->getData('action'),
                    'method' => 'post'
                ]
            ]
        );
        $form->setHtmlIdPrefix('vba_');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Import'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'data_import_file',
            'file',
            [
                'name' => 'data_import_file',
                'label' => __('Upload CSV File'),
                'title' => __('Upload CSV File'),
                'class' => 'required-entry virtual_bank',
                'after_element_html' => $this->csvfile->getElementHtml(),
                'required' => true,
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
