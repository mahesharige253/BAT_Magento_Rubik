<?php

namespace Bat\BulkOrder\Controller\Adminhtml\BulkOrder;

use Bat\BulkOrder\Model\ValidateCSVdata;
use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\Customer\Helper\Data;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\BulkOrder\Model\CreateBulkCart;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;

class Productsubmit extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
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
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CreateBulkCart
     */
    protected $bulkOrderData;

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
     * @param ScopeConfigInterface $scopeConfig
     * @param CreateBulkCart $bulkCartdata
     */
    public function __construct(
        Context $context,
        ValidateCSVdata $validateOutlet,
        ManagerInterface $messageManager,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Data $helperData,
        ScopeConfigInterface $scopeConfig,
        CreateBulkCart $bulkCartdata
    ) {
        $this->validateOutlet = $validateOutlet;
        $this->messageManager = $messageManager;
        $this->_coreRegistry = $coreRegistry;
        $this->getDataPersistor = $dataPersistor;
        $this->helperData = $helperData;
        $this->scopeConfig = $scopeConfig;
        $this->bulkOrderData = $bulkCartdata;
        parent::__construct($context, $validateOutlet, $messageManager, $coreRegistry);
    }

    /**
     * Execute function
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = __('Review Page');
        $resultPage->getConfig()->getTitle()->prepend($title);
        $params = $this->getRequest()->getParams();
        $qtyData = $params['qty'];
        $outletData = [];
        $errorMessage = [];
        $i = 0;
        foreach ($qtyData as $key => $value) {
            $outletId = $key;
            $outletData[$i]['outlet_id'] = $key;
            $outletData[$i]['parent_outlet_id'] = $params['parent_id'];
            if ($key == $params['parent_id']) {
                $outletData[$i]['is_parent'] = true;
            } else {
                $outletData[$i]['is_parent'] = false;
            }
            $skuData = $value;
            $j = 0;
            $quantity = 0;
            foreach ($skuData as $key => $value) {
                if ($value != '' && $value != 0) {
                    $value = ($value == 'on')?1:$value;
                    $outletData[$i]['items'][$j]['sku'] = $key;
                    $outletData[$i]['items'][$j]['quantity'] = $value;
                    $quantity += $value;
                    $j++;
                }
            }
            if ($quantity < $this->getMinimumCartQty()) {
                $errorMessage[$outletId] =
                    'Outlet Id ' . $outletId . ' is not having Minimum required quantity for cart';
            }
            if ($quantity > $this->getMaximumCartQty()) {
                $errorMessage[$outletId] =
                    'Outlet Id ' . $outletId . ' is not exceeding Maximum required quantity for cart';
            }
            $i++;
        }
        if (count($errorMessage) > 0) {
            foreach ($errorMessage as $key => $value) {
                $this->messageManager->addErrorMessage($value);
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $bulkOrderCarts = $this->bulkOrderData->createCart($outletData);
        $this->getDataPersistor->set('parent_id', $params['parent_id']);
        $this->getDataPersistor->set('quantity_params', $qtyData);
        return $resultPage;
    }

    /**
     * Get Minimum Cart Qty
     *
     * @return int
     */
    public function getMinimumCartQty()
    {
        return $this->scopeConfig->getValue("general_settings/general/minimum_qty_per_cart");
    }

    /**
     * Get Maximum Cart Qty
     *
     * @return int
     */
    public function getMaximumCartQty()
    {
        return $this->scopeConfig->getValue("general_settings/general/maximum_qty_per_cart");
    }
}
