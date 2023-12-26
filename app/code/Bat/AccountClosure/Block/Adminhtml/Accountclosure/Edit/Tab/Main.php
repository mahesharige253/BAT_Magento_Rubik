<?php
namespace Bat\AccountClosure\Block\Adminhtml\Accountclosure\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\Customer\Model\Entity\Attribute\Source\DisclosureApprovalStatus;
use Bat\Customer\Model\Entity\Attribute\Source\DisclosureRejectedFields;
use Bat\Customer\Model\Entity\Attribute\Source\ApprovalStatusClosure;
use Magento\Store\Model\StoreManagerInterface;
use Bat\AccountClosure\Block\Adminhtml\Accountclosure\Renderer\ProductReturn;

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
     * @var DataPersistorInterface
     */
    protected $getDataPersistor;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @var DisclosureApprovalStatus
     */
    protected $disclosureStatus;

    /**
     * @var ApprovalStatusClosure
     */
    protected $approvalStatus;

    /**
     * @var DisclosureRejectedFields
     */
    protected $disclosureRejectedFields;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Main constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param DataPersistorInterface $dataPersistor
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param DisclosureApprovalStatus $disclosureStatus
     * @param DisclosureRejectedFields $disclosureRejectedFields
     * @param StoreManagerInterface $storeManager
     * @param ApprovalStatusClosure $approvalStatus
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        DataPersistorInterface $dataPersistor,
        CustomerRepositoryInterface $customerRepositoryInterface,
        DisclosureApprovalStatus $disclosureStatus,
        DisclosureRejectedFields $disclosureRejectedFields,
        StoreManagerInterface $storeManager,
        ApprovalStatusClosure $approvalStatus,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->getDataPersistor = $dataPersistor;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->disclosureStatus = $disclosureStatus;
        $this->disclosureRejectedFields = $disclosureRejectedFields;
        $this->_storeManager = $storeManager;
        $this->approvalStatus = $approvalStatus;
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

        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Bat_AccountClosure::accountclosure')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        $id = $this->getDataPersistor->get('id');
        $outletId = $this->getDataPersistor->get('closure_outlet');
        $accountClosingDate = '';
        $return_stock = 0;
        $closureApprovalStatus = '';
        $bankAccountCard = '';
        if ($id) {
            $customerData = $this->_customerRepositoryInterface->getById($id);
            $outletId = $customerData->getCustomAttribute('outlet_id')->getValue();
            $accountClosingDate = (($customerData->getCustomAttribute('account_closing_date') != null))?
                                $customerData->getCustomAttribute('account_closing_date')->getValue():'';
            $return_stock = (($customerData->getCustomAttribute('returning_stock') != null))?
                                $customerData->getCustomAttribute('returning_stock')->getValue():0;
            $closureApprovalStatus = (($customerData->getCustomAttribute('approval_status') != null))?
                                $customerData->getCustomAttribute('approval_status')->getValue():'';
            $constentForm = (($customerData->getCustomAttribute('disclosure_consent_form_selected') != null))?
                                $customerData->getCustomAttribute('disclosure_consent_form_selected')->getValue():'';
            $closureRejectedFields = (($customerData->getCustomAttribute('disclosure_rejected_fields') != null))?
                                $customerData->getCustomAttribute('disclosure_rejected_fields')->getValue():'';
            $bankAccountCard = (($customerData->getCustomAttribute('bank_account_card') != null))?
                                $customerData->getCustomAttribute('bank_account_card')->getValue():'';
            $closureRejectedReasons = (($customerData->getCustomAttribute('disclosure_rejected_reason') != null))?
                                $customerData->getCustomAttribute('disclosure_rejected_reason')->getValue():'';
 
        }
        $mediaDirectory = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('accountclosure_main_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Account Closure Request')]
        );

        $fieldset->addField('outletId', 'hidden', ['name' => 'outletId','value' => $outletId]);

        $fieldset->addField(
            'outlet_id',
            'text',
            [
                'name' => 'outlet_id',
                'label' => __('Outlet Id'),
                'title' => __('Outlet Id'),
                'required' => true,
                'value' => $outletId,
                'disabled' => true,
            ]
        );

        $fieldset->addField(
            'account_closing_date',
            Date::class,
            ['name' => 'account_closing_date',
            'label' => __('Account Closing Date'),
            'title' => __('Account Closing Date'),
            'required' => true,
            'date_format' => 'yyyy-MM-dd',
            'value' => $accountClosingDate,
            'disabled' => ($id)?true:false,
            ]
        );
        
        $fieldset->addField(
            'bank_account_card',
            'image',
            [
                'name' => 'bank_account_card',
                'label' => __('Bank Account Card'),
                'title' => __('Bank Account Card'),
                'data-form-part' => $this->getData('target_form'),
                'value' => ($bankAccountCard)?'customer'.$bankAccountCard:'',
            ]
        );

        $fieldset->addField(
            'returning_stock',
            'select',
            [
                'name' => 'returning_stock',
                'label' => __('Return Stock'),
                'title' => __('Return Stock'),
                'value' => $return_stock,
                'options' => ['0' => __('No'), '1' => __('Yes')],
                'disabled' => ($id)?true:false,
            ]
        );

        $fieldset->addType(
            'product_return',
            ProductReturn::class
        );

        $fieldset->addField(
            'product_return',
            'product_return',
            [
                'name' => 'product_return',
            ]
        );

        if ($id) {

            $fieldset->addField('customerId', 'hidden', ['name' => 'customerId','value' => $id]);

            $fieldset->addField(
                'approval_status',
                'select',
                [
                    'name' => 'approval_status',
                    'label' => __('Account Closure Status'),
                    'title' => __('Account Closure Status'),
                    'value' => $closureApprovalStatus,
                    'values' => $this->approvalStatus->toOptionArray(),
                ]
            );

            $fieldset->addField(
                'disclosure_rejected_fields',
                'multiselect',
                [
                    'name' => 'disclosure_rejected_fields',
                    'label' => __('Account Rejected Fields'),
                    'title' => __('Account Rejected Fields'),
                    'value' => $closureRejectedFields,
                    'values' => $this->disclosureRejectedFields->toOptionArray(),
                ]
            );

            $fieldset->addField(
                'disclosure_rejected_reason',
                'text',
                [
                    'name' => 'disclosure_rejected_reason',
                    'label' => __('Account Closure Rejected Reason'),
                    'title' => __('Account Closure Rejected Reason'),
                    'value' => $closureRejectedReasons,
                ]
            );

            $fieldset->addField(
                'disclosure_consent_form_selected',
                'select',
                [
                    'name' => 'disclosure_consent_form_selected',
                    'label' => __('Closure Consent Form Selected'),
                    'title' => __('Closure Consent Form Selected'),
                    'value' => $constentForm,
                    'options' => ['0' => __('No'), '1' => __('Yes')],
                    'disabled' => true
                ]
            );
        }

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
        return __('Account Closure Request');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Account Closure Request');
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
