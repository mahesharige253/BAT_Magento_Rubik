<?php

namespace Bat\BulkOrder\Controller\Adminhtml\BulkOrder;

use Bat\BulkOrder\Model\ValidateCSVdata;
use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\Customer\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;

class Getoutlets extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{

    /**
     * @var ValidateCSVdata
     */
    protected $validateOutlet;

    /**
     * @var Data
     */
    protected $helperData;

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
        $params = $this->getRequest()->getParams();
        $parent_outlet = $params['parent_outlet'];
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('bulkorder/bulkorder/index');
        $this->getDataPersistor->clear('select_outlet_error');
        if($this->validateOutlet->validCustomer($parent_outlet)) {
            $validOutlet = $this->validateOutlet->isOutletIdValidCustomer($parent_outlet);
            if ($validOutlet != 'success') {
                if(str_contains($validOutlet,'not approved')) {
                    $message = __('The Outlet with ID '.$parent_outlet . ' is not approved');
                } elseif(str_contains($validOutlet,'closure under review')) {
                    $message = __('The Outlet with ID '.$parent_outlet . ' is under closure review');
                } elseif(str_contains($validOutlet,'closed')) {
                    $message = __('The Outlet with ID '.$parent_outlet . ' is closed');
                } elseif(str_contains($validOutlet,'Address change')) {
                    $message = __('The Outlet with ID '.$parent_outlet . ' is under Address change request');
                } elseif(str_contains($validOutlet,'VBA change')) {
                    $message = __('The Outlet with ID '.$parent_outlet . ' is under VBA change');
                } else {
                    $message = __('The Outlet with ID '.$parent_outlet . ' is not exist');
                }
                $this->messageManager->addErrorMessage($message);
                return $resultRedirect;
            }
            $parentOutlet = $this->validateOutlet->isParentOutlet($parent_outlet);
            if (!$parentOutlet) {
                $message = __('The Outlet Id ' . $parent_outlet . ' is not a parent Outlet');
                $this->messageManager->addErrorMessage($message);
                return $resultRedirect;
            }
        } else {
            $message = __('The Outlet Id ' . $parent_outlet . ' is not exist');
            $this->messageManager->addErrorMessage($message);
            return $resultRedirect;
        }
        $childOutlets = [];
        $childOutlets = $this->helperData->getChildOutlet($parent_outlet);

        (array) $this->getDataPersistor->set('outlet_data', $childOutlets);
        $this->getDataPersistor->clear('select_outlets');
        $this->getDataPersistor->set('parent_id', $parent_outlet);

        return $resultRedirect;
    }
}
