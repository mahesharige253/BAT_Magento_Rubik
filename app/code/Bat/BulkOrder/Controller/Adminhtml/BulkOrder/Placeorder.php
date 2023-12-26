<?php

namespace Bat\BulkOrder\Controller\Adminhtml\BulkOrder;

use Bat\BulkOrder\Model\ValidateCSVdata;
use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\Customer\Helper\Data;
use Bat\BulkOrder\Model\ValidateCreateBulkOrder;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;

class Placeorder extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{

    /**
     * @var ValidateCSVdata
     */
    protected $validateOutlet;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Registry
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
      * @var ValidateCreateBulkOrder
      */
    protected $createBulkOrder;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param ValidateCSVdata $validateOutlet
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param Data $helperData
     * @param ValidateCreateBulkOrder $createBulkOrder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ValidateCSVdata $validateOutlet,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Data $helperData,
        ValidateCreateBulkOrder $createBulkOrder,
        StoreManagerInterface $storeManager
    ) {
        $this->validateOutlet = $validateOutlet;
        $this->messageManager = $messageManager;
        $this->_coreRegistry = $coreRegistry;
        $this->getDataPersistor = $dataPersistor;
        $this->helperData = $helperData;
        $this->createBulkOrder = $createBulkOrder;
        $this->storeManager = $storeManager;
        parent::__construct($context, $validateOutlet, $messageManager, $coreRegistry);
    }

    /**
     * Function to execute page
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = __('Thank You Page');
        $resultPage->getConfig()->getTitle()->prepend($title);
        $params = $this->getRequest()->getParams();
        
        $outletData = $params['placeorder'];
        $bulkOrderItem = [];
        $i=0;
        foreach ($outletData as $data) {
            $cartData = explode('_', $data);
            $bulkOrderItem[$i]['outlet_id'] = $cartData[0];
            $bulkOrderItem[$i]['masked_cart_id'] = $cartData[1];
            $i++;
        }

        $parent_id = $this->getDataPersistor->get('parent_id');
        $parentData = $this->helperData->getCustomer('outlet_id', $parent_id);
        if ($parentData->getSize() > 0) {
            $customerData = $parentData->getFirstItem();
            $customerId = $customerData->getId();
        }
        $storeId = (int) $this->storeManager->getStore()->getStoreId();
        $orderId = $this->createBulkOrder->placeorder($bulkOrderItem, true, $customerId, $storeId);
        $this->getDataPersistor->set('bulkorder_id', $orderId['bulkorder_id']);
        $childOutlets = $this->helperData->getChildOutlet($parent_id);
        $this->getDataPersistor->clear('outlet_data', $childOutlets);
        $this->getDataPersistor->clear('quantity_params', '');
        $this->getDataPersistor->clear('select_outlets');
        $this->getDataPersistor->clear('parent_id');
        return $resultPage;
    }
}
