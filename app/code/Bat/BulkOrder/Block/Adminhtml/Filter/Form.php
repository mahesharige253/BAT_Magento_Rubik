<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\BulkOrder\Block\Adminhtml\Filter;

/**
 * Adminhtml report filter form
 *
 * @author  Magento Core Team <core@magentocommerce.com>
 * @api
 * @since 100.0.2
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var array
     */
    protected $_reportTypeOptions = [];

    /**
     * @var array
     */
    protected $_fieldVisibility = [];

    /**
     * @var array
     */
    protected $_fieldOptions = [];

    /**
     * Set field visibility
     *
     * @param string $fieldId
     * @param bool $visibility
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function setFieldVisibility($fieldId, $visibility)
    {
        $this->_fieldVisibility[$fieldId] = (bool) $visibility;
    }

    /**
     * Get field visibility
     *
     * @param string $fieldId
     * @param bool $defaultVisibility
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getFieldVisibility($fieldId, $defaultVisibility = true)
    {
        if (!array_key_exists($fieldId, $this->_fieldVisibility)) {
            return $defaultVisibility;
        }
        return $this->_fieldVisibility[$fieldId];
    }

    /**
     * Set field option(s)
     *
     * @param string $fieldId Field id
     * @param mixed $option Field option name
     * @param mixed|null $value Field option value
     *
     * @return void
     */
    public function setFieldOption($fieldId, $option, $value = null)
    {
        if (is_array($option)) {
            $options = $option;
        } else {
            $options = [$option => $value];
        }
        if (!array_key_exists($fieldId, $this->_fieldOptions)) {
            $this->_fieldOptions[$fieldId] = [];
        }
        foreach ($options as $k => $v) {
            $this->_fieldOptions[$fieldId][$k] = $v;
        }
    }

    /**
     * Add report type option
     *
     * @param string $key
     * @param string $value
     * @return $this
     * @codeCoverageIgnore
     */
    public function addReportTypeOption($key, $value)
    {
        $this->_reportTypeOptions[$key] = __($value);
        return $this;
    }

    /**
     * Add fieldset with general report fields
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $actionUrl = $this->getUrl('*/*/searchoutlet');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'bulkorder_form',
                    'action' => $actionUrl,
                    'method' => 'post'
                ]
            ]
        );

        $htmlIdPrefix = 'sales_report_';
        $form->setHtmlIdPrefix($htmlIdPrefix);
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Search Outlets')]);

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);

        $fieldset->addField('store_ids', 'hidden', ['name' => 'store_ids']);

        $fieldset->addField(
            'parent_outlet',
            'text',
            [
                'name' => 'parent_outlet',
                'label' => __('Parent Outlet')
            ]
        );

        $fieldset->addButton(
            'formsubmit',
            'submit',
            [
                'name' => 'formsubmit',
                'label' => __('Search Outlet')
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
    
    /**
     * This method is called before rendering HTML
     *
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _beforeToHtml()
    {
        $result = parent::_beforeToHtml();

        /** @var \Magento\Framework\Data\Form\Element\Fieldset $fieldset */
        $fieldset = $this->getForm()->getElement('base_fieldset');

        if (is_object($fieldset) && $fieldset instanceof \Magento\Framework\Data\Form\Element\Fieldset) {
            // apply field visibility
            foreach ($fieldset->getElements() as $field) {
                if (!$this->getFieldVisibility($field->getId())) {
                    $fieldset->removeField($field->getId());
                }
            }
            // apply field options
            foreach ($this->_fieldOptions as $fieldId => $fieldOptions) {
                $field = $fieldset->getElements()->searchById($fieldId);
                /** @var \Magento\Framework\DataObject $field */
                if ($field) {
                    foreach ($fieldOptions as $k => $v) {
                        $field->setDataUsingMethod($k, $v);
                    }
                }
            }
        }

        return $result;
    }
}
