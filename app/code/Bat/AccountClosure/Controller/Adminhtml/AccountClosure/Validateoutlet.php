<?php

namespace Bat\AccountClosure\Controller\Adminhtml\AccountClosure;

use Bat\BulkOrder\Model\ValidateCSVdata;
use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\Customer\Helper\Data;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Validateoutlet extends \Magento\Backend\App\Action implements HttpPostActionInterface
{

    /**
     * @var ValidateCSVdata
     */
    protected $validateOutlet;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var DataPersistorInterface
     */
    protected $getDataPersistor;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * Construct method
     *
     * @param Context $context
     * @param ValidateCSVdata $validateOutlet
     * @param ManagerInterface $messageManager
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param Data $helperData
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        ValidateCSVdata $validateOutlet,
        ManagerInterface $messageManager,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Data $helperData,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->validateOutlet = $validateOutlet;
        $this->messageManager = $messageManager;
        $this->_coreRegistry = $coreRegistry;
        $this->getDataPersistor = $dataPersistor;
        $this->helperData = $helperData;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $validateOutlet, $messageManager, $coreRegistry);
    }

    /**
     * Execute function
     */
    public function execute()
    {
        
        $params = $this->getRequest()->getParams();
        $resultRedirect = $this->resultRedirectFactory->create();
        $outlet = $params['outlet'];
        $collection = $this->validateOutlet->getCustomer('outlet_id', $outlet);
        $errorMessage = 0;

        if ($collection->getSize() > 0) {
            $customer = $collection->getFirstItem();
            $customerDetatils = $this->customerRepository->getById($customer->getId());
            $closureStatus = '';
            if (!empty($customerDetatils->getCustomAttribute('approval_status'))) {
                $closureStatus = $customerDetatils->getCustomAttribute('approval_status')->getValue();
            }

            if ($closureStatus == 14) {
                $message = __('The Outlet with ID '.$outlet . ' is already requested closure');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            }

            if ($closureStatus == 6) {
                $message = __('The Outlet with ID '.$outlet . ' is under review of account closure');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            }

            if ($closureStatus == 7) {
                $message = __('The Outlet with ID '.$outlet . ' is already closed account');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            }

            if ($closureStatus == 9) {
                $message = __('The Outlet with ID '.$outlet . ' is already terminated');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            }

            if ($closureStatus == 10) {
                $message = __('The Outlet with ID '.$outlet . ' is under refund in-progress');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            }

            if ($closureStatus == 11) {
                $message = __('The Outlet with ID '.$outlet . ' is under collection in-progress');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            }

        } else {
            $message = __('The Outlet with ID '.$outlet . ' is not exist');
            $this->messageManager->addErrorMessage($message);
            $errorMessage = 1;
        }

        if ($errorMessage == 1) {
            $resultRedirect->setPath('accountclosure/accountclosure/searchoutlet');
            return $resultRedirect;
        }

        $this->getDataPersistor->set('closure_outlet', $outlet);

        $resultRedirect->setPath('accountclosure/accountclosure/addnew');
        return $resultRedirect;
    }
}
