<?php

namespace Bat\Rma\Controller\Adminhtml\CreateReturns;

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
use Bat\Rma\Helper\Data as RmaHelper;

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
     * @var RmaHelper
     */
    private RmaHelper $rmaHelper;

    /**
     * @param Context $context
     * @param ValidateCSVdata $validateOutlet
     * @param ManagerInterface $messageManager
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param Data $helperData
     * @param CustomerRepositoryInterface $customerRepository
     * @param RmaHelper $rmaHelper
     */
    public function __construct(
        Context $context,
        ValidateCSVdata $validateOutlet,
        ManagerInterface $messageManager,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Data $helperData,
        CustomerRepositoryInterface $customerRepository,
        RmaHelper $rmaHelper
    ) {
        $this->validateOutlet = $validateOutlet;
        $this->messageManager = $messageManager;
        $this->_coreRegistry = $coreRegistry;
        $this->getDataPersistor = $dataPersistor;
        $this->helperData = $helperData;
        $this->customerRepository = $customerRepository;
        $this->rmaHelper = $rmaHelper;
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
            if ($closureStatus != 1) {
                $message = __('The Outlet with ID '.$outlet . ' should be an approved customer');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            } else {
                $orderCompleted = $this->rmaHelper->checkCustomerCompletedOrder($customerDetatils->getId());
                if (!$orderCompleted) {
                    $this->messageManager->addErrorMessage('Customer has not completed any order');
                    $errorMessage = 1;
                }
            }
        } else {
            $message = __('The Outlet with ID '.$outlet . ' is not exist');
            $this->messageManager->addErrorMessage($message);
            $errorMessage = 1;
        }
        if ($errorMessage == 1) {
            $resultRedirect->setPath('returns/createreturns/searchoutlet');
            return $resultRedirect;
        }
        $this->getDataPersistor->set('return_request_outlet', $outlet);
        $resultRedirect->setPath('returns/createreturns/addnew');
        return $resultRedirect;
    }
}
