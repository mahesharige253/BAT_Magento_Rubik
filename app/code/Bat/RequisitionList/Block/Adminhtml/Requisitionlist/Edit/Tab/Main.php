<?php
namespace Bat\RequisitionList\Block\Adminhtml\Requisitionlist\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Bat\RequisitionList\Ui\Component\Listing\Column\RlType;

class Main extends Generic implements TabInterface
{
    /**
     * @var Store
     */
    protected $systemStore;

    /**
     * @var AssignTypes
     */
    protected $assignTypes;

    /**
     * @var RlType
     */
    protected $rlType;

    /**
     * Main constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param RlType $rlType
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        RlType $rlType,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->rlType = $rlType;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return Main
     * @throws LocalizedException
     */
    public function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('requisitionlist');

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Bat_RequisitionList::requisitionlist')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('requisitionlist_main_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Requisition List Information')]
        );

        if ($model->getEntityId()) {
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Enabled'),
                'title' => __('Enabled'),
                'required' => true,
                'value' => 1,
                'options' => ['1' => __('Yes'), '0' => __('No')],
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'title' => __('Name'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'description',
            'textarea',
            [
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'rl_type',
            'select',
            [
                'name' => 'rl_type',
                'label' => __('RL Type'),
                'title' => __('RL Type'),
                'required' => true,
                'value' => 0,
                'options' => $this->rlType->getOptionArray(),
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'seasonal_percentage',
            'text',
            [
                'name' => 'seasonal_percentage',
                'label' => __('Seasonal Percentage'),
                'title' => __('Seasonal Percentage'),
                'required' => false,
                'disabled' => $isElementDisabled
            ]
        );

        if ($model->getEntityId()) {
            $model->setData('assign_type', 1);
            $form->setValues($model->getData());
        }

        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Requisition List Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Requisition List Information');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
