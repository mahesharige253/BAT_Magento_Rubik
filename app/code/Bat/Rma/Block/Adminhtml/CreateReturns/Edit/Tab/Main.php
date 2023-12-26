<?php
namespace Bat\Rma\Block\Adminhtml\CreateReturns\Edit\Tab;

use Bat\Rma\Model\Source\ReturnSwiftCode;
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
use Magento\Store\Model\StoreManagerInterface;
use Bat\Rma\Block\Adminhtml\CreateReturns\Renderer\ProductReturn;

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
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ReturnSwiftCode
     */
    private ReturnSwiftCode $returnSwiftCode;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param DataPersistorInterface $dataPersistor
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param StoreManagerInterface $storeManager
     * @param ReturnSwiftCode $returnSwiftCode
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        DataPersistorInterface $dataPersistor,
        CustomerRepositoryInterface $customerRepositoryInterface,
        StoreManagerInterface $storeManager,
        ReturnSwiftCode $returnSwiftCode,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->getDataPersistor = $dataPersistor;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->_storeManager = $storeManager;
        $this->returnSwiftCode = $returnSwiftCode;
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
        if ($this->_isAllowedAction('Bat_Rma::createRma')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        $id = $this->getDataPersistor->get('id');
        $outletId = $this->getDataPersistor->get('return_request_outlet');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('accountclosure_main_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Create Return Request')]
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
            'return_swift_code',
            'select',
            [
                'name' => 'return_swift_code',
                'label' => __('Return Reason'),
                'title' => __('Return Reason'),
                'value' => '',
                'required' => true,
                'values' => $this->returnSwiftCode->toOptionArray(),
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
        return __('New Return Request');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('New Return Request');
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
