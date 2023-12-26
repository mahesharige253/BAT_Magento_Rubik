<?php

namespace Bat\BulkOrder\Controller\Adminhtml\BulkOrder;

use Bat\BulkOrder\Model\ValidateCSVdata;
use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\Customer\Helper\Data;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;

class Validoutlet extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
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
     * Construct method
     *
     * @param Context $context
     * @param ValidateCSVdata $validateOutlet
     * @param ManagerInterface $messageManager
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param Data $helperData
     */
    public function __construct(
        Context $context,
        ValidateCSVdata $validateOutlet,
        ManagerInterface $messageManager,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Data $helperData
    ) {
        $this->validateOutlet = $validateOutlet;
        $this->messageManager = $messageManager;
        $this->_coreRegistry = $coreRegistry;
        $this->getDataPersistor = $dataPersistor;
        $this->helperData = $helperData;
        parent::__construct($context, $validateOutlet, $messageManager, $coreRegistry);
    }

    /**
     * Execute function
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = __('Select Products');
        $resultPage->getConfig()->getTitle()->prepend($title);
        $params = $this->getRequest()->getParams();
        $outlets = (isset($params['outlet'])) ? $params['outlet'] : '';
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('bulkorder/bulkorder/index');
        $this->getDataPersistor->clear('select_outlet_error');
        if ($outlets == '') {
            $message = __('Please select the outlets to proceed');
            $this->messageManager->addErrorMessage($message);
            $this->getDataPersistor->set('select_outlet_error', 1);
            return $resultRedirect;
        }
        $errorMessage = 0;
        $overPaymentError = [];
        $orderFrequencyError = [];
        $failedOrdersError = [];
        foreach ($outlets as $outlet) {
            $outletId = explode(':', $outlet);
            if($this->validateOutlet->validCustomer($outletId[0])) {
                $validOutlet = $this->validateOutlet->isOutletIdValidCustomer($outletId[0]);
                if ($validOutlet != 'success') {
                    if(str_contains($validOutlet,'not approved')) {
                        $message = __('The Outlet with ID '.$outletId[0] . ' is not approved');
                    } elseif(str_contains($validOutlet,'closure under review')) {
                        $message = __('The Outlet with ID '.$outletId[0] . ' is under closure review');
                    } elseif(str_contains($validOutlet,'closed')) {
                        $message = __('The Outlet with ID '.$outletId[0] . ' is closed');
                    } elseif(str_contains($validOutlet,'Address change')) {
                        $message = __('The Outlet with ID '.$outletId[0] . ' is under Address change request');
                    } elseif(str_contains($validOutlet,'VBA change')) {
                        $message = __('The Outlet with ID '.$outletId[0] . ' is under VBA change');
                    } else {
                        $message = __('The Outlet with ID '.$outletId[0] . ' is not exist');
                    }
                    $this->messageManager->addErrorMessage($message);
                    $errorMessage = 1;
                }

                $getOrderFrequencyStatus = $this->validateOutlet->getOrderFrequencyData($outletId[0]);

                if ($getOrderFrequencyStatus != '') {
                    $orderFrequencyError[] = $outletId[0];
                    $errorMessage =1;
                }

                $failedOrders = $this->validateOutlet->getCustomerFailedOrders($outletId[0]);
                if ($failedOrders != '') {
                    $failedOrdersError[] = $outletId[0];
                    $errorMessage =1;
                }

                $getPaymentOverDue = $this->validateOutlet->getOverDueData($outletId[0]);

                if ($getPaymentOverDue != '') {
                    $overPaymentError[] = $outletId[0];
                    $errorMessage =1;
                }
            } else {
                $message = __('The Outlet with ID '.$outletId[0] . ' is not exist');
                $this->messageManager->addErrorMessage($message);
                $errorMessage = 1;
            }
        }
        if (count($orderFrequencyError) > 0) {
            $overPaymentData = implode(',', $orderFrequencyError);
            $this->messageManager->addErrorMessage('Outlets '.$overPaymentData.' exceeds order frequency');
        }

        if (count($failedOrdersError) > 0) {
            $failedOrdersMessage = implode(',', $failedOrdersError);
            $this->messageManager->addErrorMessage('Outlets '.$failedOrdersMessage.' Unconfirmed/Unpaid Orders');
        }

        if (count($overPaymentError) > 0) {
            $overPaymentData = implode(',', $overPaymentError);
            $this->messageManager->addErrorMessage('Outlets '.$overPaymentData.' having overdue payment');
        }
        $this->getDataPersistor->set('select_outlets', $outlets);
        $this->getDataPersistor->set('parent_id', $params['parent_id']);

        if ($errorMessage != 0) {
            return $resultRedirect;
        }
        (array) $this->getDataPersistor->set('valid_outlet', $outlets);
        $this->getDataPersistor->clear('quantity_params');
        return $resultPage;
    }
}
