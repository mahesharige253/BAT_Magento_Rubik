<?php
namespace Bat\Sales\Helper;

use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource\Collection;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource\CollectionFactory as EdaOrdersCollectionFactory;
use Bat\Sales\Model\SendOrderDetails;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use \Magento\Sales\Model\Convert\Order as Shipment;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Bat\Sales\Model\EdaOrderType;
use Bat\Sales\Model\BatOrderStatus;
use Bat\Customer\Helper\Data as CustomerHelper;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Bat\Customer\Helper\Data as CustomerHelperData;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollection;
use Magento\Directory\Model\Currency;
use Magento\Framework\Currency\Data\Currency as CurrencyData;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;

/**
 * @class Data
 * Helper Class for Eda Create/Update orders
 */
class Data extends AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $orderCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepositoryInterface;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $date;

    /**
     * @var InvoiceService
     */
    private InvoiceService $invoiceService;

    /**
     * @var Transaction
     */
    private Transaction $transaction;

    /**
     * @var Shipment
     */
    private Shipment $shipment;

    /**
     * @var EdaOrdersCollectionFactory
     */
    private EdaOrdersCollectionFactory $edaOrdersCollectionFactory;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var CustomerHelper
     */
    private CustomerHelper $customerHelper;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quote;

    /**
     * @var AddressRepositoryInterface
     */
    private AddressRepositoryInterface $addressRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var CustomerHelperData
     */
    private CustomerHelperData $helperData;

    /**
     * @var OrderInterface
     */
    private OrderInterface $orderInterface;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var IndexerCollection
     */
    private IndexerCollection $indexerCollectionFactory;

    /**
     * @var Currency
     */
    protected Currency $currency;

    /**
     * @var CategoryFactory
     */
    private CategoryFactory $categoryFactory;

      /**
     * @var GetDiscountMessage
     */
    protected GetDiscountMessage $getDiscountMessage;

    /**
     * @var BalanceFactory
     */
    private BalanceFactory $_balanceFactory;

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $date
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param Shipment $shipment
     * @param EdaOrdersCollectionFactory $edaOrdersCollectionFactory
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param LoggerInterface $logger
     * @param KakaoSms $kakaoSms
     * @param CustomerHelperData $customerHelper
     * @param QuoteManagement $quoteManagement
     * @param QuoteFactory $quote
     * @param AddressRepositoryInterface $addressRepository
     * @param ProductRepositoryInterface $productRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param CompanyManagementInterface $companyManagement
     * @param StockRegistryInterface $stockRegistry
     * @param DateTimeFactory $dateTimeFactory
     * @param CustomerHelperData $helperData
     * @param OrderInterface $orderInterface
     * @param StoreManagerInterface $storeManager
     * @param IndexerCollection $indexerCollectionFactory
     * @param Currency $currency
     * @param CategoryFactory $categoryFactory
     * @param GetDiscountMessage $getDiscountMessage
     * @param BalanceFactory $balanceFactory
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $date,
        InvoiceService $invoiceService,
        Transaction $transaction,
        Shipment $shipment,
        EdaOrdersCollectionFactory $edaOrdersCollectionFactory,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        LoggerInterface $logger,
        KakaoSms $kakaoSms,
        CustomerHelper $customerHelper,
        QuoteManagement $quoteManagement,
        QuoteFactory $quote,
        AddressRepositoryInterface $addressRepository,
        ProductRepositoryInterface $productRepository,
        OrderRepositoryInterface $orderRepository,
        CompanyManagementInterface $companyManagement,
        StockRegistryInterface $stockRegistry,
        DateTimeFactory $dateTimeFactory,
        CustomerHelperData $helperData,
        OrderInterface $orderInterface,
        StoreManagerInterface $storeManager,
        IndexerCollection $indexerCollectionFactory,
        Currency $currency,
        CategoryFactory $categoryFactory,
        GetDiscountMessage $getDiscountMessage,
        BalanceFactory $balanceFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
        $this->date =  $date;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->shipment = $shipment;
        $this->edaOrdersCollectionFactory = $edaOrdersCollectionFactory;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->logger = $logger;
        $this->kakaoSms = $kakaoSms;
        $this->customerHelper = $customerHelper;
        $this->quoteManagement = $quoteManagement;
        $this->quote = $quote;
        $this->addressRepository = $addressRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->companyManagement = $companyManagement;
        $this->stockRegistry = $stockRegistry;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->helperData = $helperData;
        $this->orderInterface = $orderInterface;
        $this->storeManager = $storeManager;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->currency = $currency;
        $this->categoryFactory = $categoryFactory;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->_balanceFactory = $balanceFactory;
    }

    /**
     * Create Order In EDA Logs
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logEdaOrderUpdateRequest($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaOrder.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Format Date to Ymd
     *
     * @param string $date
     * @return string
     */
    public function formatDate($date)
    {
        return $this->date->date($date)->format('Ymd');
    }

    /**
     * Generate Unique Batch ID
     *
     * @param string $orderId
     * @return string
     */
    public function getUniqueBatchId($orderId)
    {
        $dateTime = $this->date->date()->format('Y-m-d H:i:s');
        return $orderId.strtotime($dateTime);
    }

    /**
     * Return System Configuration value
     *
     * @param string $path
     * @return mixed
     */
    public function getSystemConfigValue($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Return Delivery Date
     *
     * @return mixed
     */
    public function getDeliveryDate()
    {
        $days = '+'.$this->getSystemConfigValue('bat_integrations/bat_order/delivery_date_order_push_eda').' Days';
        return $this->date->date()->modify($days)->format('Ymd');
    }

    /**
     * Generate Invoice
     *
     * @param OrderInterface $order
     * @param string $comment
     * @return bool
     * @throws LocalizedException
     */
    public function createInvoice($order, $comment)
    {
        $status = false;
        if ($order->canInvoice()) {
            $this->createShipment($order);
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setGrandTotal($order->getGrandTotal());
            $invoice->setBaseGrandTotal($order->getGrandTotal());
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();
            $order->setIsShipmentAvailable(1);
            $order->setAction('shipment_created');
            $order->addCommentToStatusHistory(
                __($comment)
            )->setIsCustomerNotified(false);
            $order->save();
            $status = true;
        }
        return $status;
    }

    /**
     * Create Shipment
     *
     * @param OrderInterface $order
     */
    public function createShipment($order)
    {
        if (! $order->canShip()) {
            throw new LocalizedException(
                __('You can\'t create an shipment.')
            );
        }
        $shipmentOrder = $this->shipment->toShipment($order);
        foreach ($order->getAllItems() as $orderItem) {
            if (! $orderItem->getQtyToShip()) {
                throw new LocalizedException(
                    __('Cannot create shipment for orderItem'.$orderItem->getName())
                );
            }
            $qtyShipped = $orderItem->getQtyToShip();
            $shipmentItem = $this->shipment->itemToShipmentItem($orderItem)->setQty($qtyShipped);
            $shipmentOrder->addItem($shipmentItem);
        }
        $shipmentOrder->register();
        $shipmentOrder->getOrder()->setIsInProcess(true);
        $shipmentOrder->save();
        $shipmentOrder->getOrder()->save();
    }

    /**
     * Get order for EDA Update
     *
     * @param string $orderId
     * @return DataObject|string
     */
    public function getOrderForEdaUpdate($orderId, $channel)
    {
        $collection =  $this->edaOrdersCollectionFactory->create()->addFieldToSelect('*')
            ->addFieldToFilter('order_id', ['eq'=>$orderId])
            ->addFieldToFilter('channel', ['eq'=>$channel]);
        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }
        return '';
    }

    /**
     * Update Order to EDA table
     *
     * @param string $orderId
     * @param string $orderType
     * @param string $channel
     * @param string $incrementId
     */
    public function updateOrderToEda($orderId, $orderType, $channel, $incrementId)
    {
        $updated = ['success'=>false,'message'=>''];
        try {
            $edaOrder = $this->getOrderForEdaUpdate($orderId, $channel);
            if ($edaOrder != '') {
                $edaOrder->setOrderType($orderType);
                $edaOrder->setChannel($channel);
                $edaOrder->setFailureAttempts(0);
                $edaOrder->setOrderSent(0);
                $edaOrder->setOrderIncrementId($incrementId);
                $this->edaOrdersResource->save($edaOrder);
            } else {
                $edaOrder = $this->edaOrdersFactory->create();
                $edaOrder->setData(
                    [
                        'order_id' => $orderId,
                        'order_type' => $orderType,
                        'channel' => $channel,
                        'order_increment_id' => $incrementId
                    ]
                );
                $this->edaOrdersResource->save($edaOrder);
            }
            $updated['success'] = true;
        } catch (\Exception $e) {
            $updated['message'] = $e->getMessage();
            $this->logger->info('Update order to Eda table failed'.$e->getMessage());
        }
        return $updated;
    }

    /**
     * send sms for shipment started
     *
     * @param OrderInterface $order
     * @param string $trackNumber
     */
    public function sendShipmentStartedSms($order, $trackNumber)
    {
        $smsSent = ['success' =>true,'message' => ''];
        try {
            $totalQty = 0;
            $firstItemName = '';
            $i = 0;
            $totalAmount = 0;
            foreach ($order->getAllItems() as $item) {
                if ($item->getIsPriceTag() == 0) {
                    $firstItemName = $item->getName();
                    $totalQty = $totalQty + $item->getQtyOrdered();
                    $i++;
                }
            }
            if ($i > 1) {
                $firstItemName = $firstItemName.' 외 '.(--$i).' 개';
            }
            /** @var CustomerInterface $customer */
            $customer = $this->customerRepositoryInterface->getById($order->getCustomerId());
            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            $address = $this->customerHelper->getCustomerDefaultShippingAddress($customer);
            $params = [
                'salesorder_number' => $order->getIncrementId(),
                '1stsalesorderproduct_others' => $firstItemName,
                'totalsalesorder_qty' => $totalQty,
                'deliverytrackinginfo_number' => $trackNumber,
                'outlet_address' => $address
            ];
            $this->kakaoSms->sendSms($mobileNumber, $params, 'ShipmentNotice_001');
        } catch (\Exception $e) {
            $smsSent['success'] = false;
            $smsSent['message'] = $e->getMessage();
        }
        return $smsSent;
    }

    /**
     * Create New Order for Delivery Failure
     *
     * @param OrderInterface $order
     * @param Item[] $orderItems
     */
    public function createOrderForDeliveryFailure($order, $orderItems)
    {
        $orderCreated = ['success' => false,'order_id' => ''];
        $productIdsForStatusUpdate = [];
        try {
            $customerId = $order->getCustomerId();
            $storeId = $order->getStoreId();
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $quote = $this->quote->create();
            $quote->setStoreId($this->getDefaultStoreId());
            $quote->assignCustomer($customer);
            $adminStoreId = $this->getAdminStoreId();
            foreach ($orderItems as $item) {
                $product = $this->productRepository->getById($item->getProductId());
                $product->setStoreId($adminStoreId);
                $productIdsForStatusUpdate[] = $product->getId();
                $stockData = $product->getStockData();
                $stockData['manage_stock'] = 0;
                $stockData['use_config_manage_stock'] = 0;
                $product->setStockData($stockData);
                $product = $this->productRepository->save($product);
                $quote->addProduct($product, $item->getQtyOrdered());
            }
            $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
            $shippingAddress = $quote->getShippingAddress()->setCustomerAddressId($shippingAddress->getId());
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('flatrate_flatrate');
            $quote->setInventoryProcessed(true);
            $quote->setUseCustomerBalance(false);
            $quote->setEdaOrderType(EdaOrderType::ZLOB);
            $quote->collectTotals()->save();
            if ($quote->getGrandTotal() == 0) {
                $quote->setPaymentMethod('free');
                $quote->getPayment()->importData(['method' => 'free']);
            } else {
                $quote->setPaymentMethod('banktransfer');
                $quote->getPayment()->importData(['method' => 'banktransfer']);
            }
            $newOrder = $this->quoteManagement->submit($quote);
            $newOrder->setOrderType(__('Canceled Order'));
            $newOrder->setReturnSwiftReason('001');
            $newOrder->setReturnOriginalOrderId($order->getIncrementId());
            $this->orderRepository->save($newOrder);
            $order->setReturnOriginalOrderId($newOrder->getIncrementId());
            $order->addCommentToStatusHistory(
                __('ZLOB Order Created : '.$newOrder->getIncrementId())
            )->setIsCustomerNotified(false);
            $this->orderRepository->save($order);
            $this->updateProduct($productIdsForStatusUpdate);
            $orderCreated['success'] = true;
            $orderCreated['order_id'] = $newOrder->getEntityId();
            if($order->getDiscountAmount() != '' && $order->getAppliedRuleIds() != ''){
                $this->getDiscountMessage->setCustomerTimesUsed($order->getCustomerId(), $order->getAppliedRuleIds());
            }
        } catch (\Exception $e) {
            $this->logger->info('ZLOB order exception : '.$e->getMessage());
            $orderCreated['success'] = false;
        }
        return $orderCreated;
    }

    /**
     * Send sms to customer on return completion
     *
     * @param OrderInterface $order
     */
    public function sendReturnCompletedMessage($order)
    {
        try {
            $totalQty = 0;
            $firstItemName = '';
            $totalAmount = 0;
            $totalReturnedItems = [];
            $returnOriginalOrderId = explode(', ', $order->getReturnOriginalOrderId());
            if (empty($returnOriginalOrderId)) {
                return;
            }
            foreach ($returnOriginalOrderId as $returnOrderId) {
                $returnOrder = $this->orderInterface->loadByIncrementId($returnOrderId);
                $returnOrder->getTotalQtyOrdered();
                foreach ($returnOrder->getAllItems() as $item) {
                    if ($firstItemName == '') {
                        $firstItemName = $item->getName();
                    }
                    $totalReturnedItems[$item->getSku()] = $item->getName();
                }
                $totalQty = $totalQty + $returnOrder->getTotalQtyOrdered();
                $totalAmount = $totalAmount + $returnOrder->getTotalPaid();
            }
            $totalItemCount = count($totalReturnedItems);
            if ($totalItemCount > 1) {
                $firstItemName = $firstItemName.' 외 '.(--$totalItemCount).' 개';
            }
            /** @var CustomerInterface $customer */
            $customer = $this->customerHelper->getCustomerById($order->getCustomerId());
            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            $params = [];
            $outletName = '';
            $company = $this->companyManagement->getByCustomerId($customer->getId());
            if ($company) {
                $outletName = $company->getCompanyName();
            }
            $templateCode = 'ReturnComplete_001';
            $params = [
                'returnrequest_date' => date("Y년 m월 d일", strtotime($order->getCreatedAt())),
                '1streturncproduct_others' => $firstItemName,
                'totalreturn_qty' => $totalQty,
                'totalreturn_amount' => $this->currency->format(
                    $totalAmount,
                    ['display'=> CurrencyData::NO_SYMBOL, 'precision' => 0],
                    false
                ),
                'outlet_name' => $outletName
            ];
            $this->kakaoSms->sendSms($mobileNumber, $params, $templateCode);
        } catch (\Exception $e) {
            $this->logger->info('Return complete message exception for Order Id'
                .$order->getEntityId().' '.$e->getMessage());
        }
    }

    /**
     * Send sms to customer on return closed
     *
     * @param OrderInterface $order
     */
    public function sendReturnClosedMessage($order)
    {
        try {
            $totalQty = 0;
            $firstItemName = '';
            $totalAmount = 0;
            $totalReturnedItems = [];
            $returnOriginalOrderId = explode(', ', $order->getReturnOriginalOrderId());
            if (empty($returnOriginalOrderId)) {
                return;
            }
            foreach ($returnOriginalOrderId as $returnOrderId) {
                $returnOrder = $this->orderInterface->loadByIncrementId($returnOrderId);
                $returnOrder->getTotalQtyOrdered();
                foreach ($returnOrder->getAllItems() as $item) {
                    if ($firstItemName == '') {
                        $firstItemName = $item->getName();
                    }
                    $totalReturnedItems[$item->getSku()] = $item->getName();
                }
                $totalQty = $totalQty + $returnOrder->getTotalQtyOrdered();
                $totalAmount = $totalAmount + $returnOrder->getTotalPaid();
            }
            $totalItemCount = count($totalReturnedItems);
            if ($totalItemCount > 1) {
                $firstItemName = $firstItemName.' 외 '.(--$totalItemCount).' 개';
            }
            /** @var CustomerInterface $customer */
            $customer = $this->customerHelper->getCustomerById($order->getCustomerId());
            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            $params = [];
            $outletName = '';
            $company = $this->companyManagement->getByCustomerId($customer->getId());
            if ($company) {
                $outletName = $company->getCompanyName();
            }
            $templateCode = 'ClosingReturn_001';
            $params = [
                'outlet_name' => $outletName,
                '1streturnproduct_others' => $firstItemName,
                'totalreturn_qty' => $totalQty
            ];
            $this->kakaoSms->sendSms($mobileNumber, $params, $templateCode);
        } catch (\Exception $e) {
            $this->logger->info('Return complete message exception for Order Id'
                .$order->getEntityId().' '.$e->getMessage());
        }
    }

    /**
     * Return order based on sap order number
     *
     * @param string $orderNumber
     */
    public function getOrderOnSapOrderNumber($orderNumber)
    {
        $collection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('sap_order_number', $orderNumber);
        return $collection;
    }

    /**
     * Get Item Custom Price
     *
     * @param \Magento\Sales\Model\Order\Item[] $orderItems
     * @param OrderInterface $order
     * @param null $requestType
     * @return array
     */
    public function getItemCustomPriceForNewOrder($orderItems, $order, $requestType = null)
    {
        $result = ['success' => false,'custom_price'=> []];
        $totalDiscountAmount = abs($order->getDiscountAmount());
        if ($totalDiscountAmount || $requestType == 'ZRE1') {
            $result['success'] = true;
            $totalQtyOrderedExcludingPriceTag = 0;
            foreach ($orderItems as $orderItem) {
                if ($orderItem->getIsPriceTag()) {
                    continue;
                }
                $totalQtyOrderedExcludingPriceTag = $totalQtyOrderedExcludingPriceTag + $orderItem->getQtyOrdered();
            }
            $discountAmountPerItem = $totalDiscountAmount/$totalQtyOrderedExcludingPriceTag;
            foreach ($orderItems as $orderItem) {
                if ($orderItem->getIsPriceTag()) {
                    continue;
                }
                $price = $orderItem->getPrice();
                if ($totalDiscountAmount > 0) {
                    $price = ($price - $discountAmountPerItem);
                }
                $result['custom_price'][$orderItem->getSku()] = $price;
            }
        }
        return $result;
    }

    /**
     * Update manage stock and product status
     *
     * @param $productIds
     * @throws LocalizedException
     */
    public function updateProduct($productIds)
    {
        $adminStoreId = $this->getAdminStoreId();
        foreach ($productIds as $productId) {
            try {
                $product = $this->productRepository->getById($productId);
                $stockData = $product->getStockData();
                $stockData['manage_stock'] = 1;
                $stockData['use_config_manage_stock'] = 1;
                $product->setStockData($stockData);
                $product->setStoreId($adminStoreId);
                $this->productRepository->save($product);
            } catch (\Exception $e) {
                $this->logger->info('Product Disable and Manage stock exception : '.$e->getMessage());
            }
        }
    }

    /**
     * Send Return in progress Kakao Message
     *
     * @param OrderInterface $order
     */
    public function sendReturnInProgressMessage($order)
    {
        try {
            $customerId = $order->getCustomerId();
            $customer = $this->customerRepositoryInterface->getById($customerId);
            if ($order->getReturnSwiftCode() == 10) {
                return;
            }
            $totalItemCount = $order->getTotalItemCount();
            $orderItems = $order->getAllItems();
            $returnItemName = '';
            foreach ($orderItems as $item) {
                $returnItemName = $item->getName();
                break;
            }
            if ($totalItemCount > 1) {
                $returnItemName = $returnItemName .' 외 '. $totalItemCount - 1 .' 개';
            }
            if ($customer->getCustomAttribute('mobilenumber')) {
                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                $outletName = ($this->helperData->getInfo($customerId)) ? $this->helperData->getInfo($customerId) : '';
                $outletAddress = ($this->helperData->getCustomerDefaultShippingAddress($customer))
                    ? $this->helperData->getCustomerDefaultShippingAddress($customer) : '';
                $dateModel = $this->dateTimeFactory->create();
                $rmaRequestDate = ($dateModel->gmtDate()) ? $dateModel->gmtDate() : '';
                /* Kakao SMS for Customer order return request */
                $params = [
                    'returnrequest_date' => date("Y년 m월 d일", strtotime($rmaRequestDate)),
                    'outlet_name' => $outletName,
                    'outlet_address' => $outletAddress,
                    '1streturnrequestproduct_others' => $returnItemName,
                    'totalreturnrequest_qty' => $order->getTotalQtyOrdered()
                ];
                $this->kakaoSms->sendSms($mobileNumber, $params, 'ReturnRequest_002');
            }
        } catch (\Exception $e) {
            $this->logger->info(
                'Return request message not sent : '.$order->getIncrementId().' '.$e->getMessage()
            );
        }
    }

    /**
     * Validate and send return completed message
     *
     * @param OrderInterface $order
     * @return bool|void
     */
    public function checkAllReturnOrderSentToIntegration($order)
    {
        try {
            $iroOrder = $order->getReturnOriginalOrderId();
            if ($iroOrder != '') {
                $iroOrder = $this->orderInterface->loadByIncrementId($iroOrder);
                $returnOrdersId = $iroOrder->getReturnOriginalOrderId();
                if ($returnOrdersId != '') {
                    $returnOrdersId = explode(', ', $returnOrdersId);
                    $collection =  $this->edaOrdersCollectionFactory->create()->addFieldToSelect('*')
                        ->addFieldToFilter('order_increment_id', ['in'=>$returnOrdersId])
                        ->addFieldToFilter('order_sent', [['eq'=>''],['eq'=>'0']]);
                    if ($collection->getSize()) {
                        return;
                    } else {
                        $iroOrder->setAction('return_complete');
                        $iroOrder->setStatus(BatOrderStatus::COMPLETED_STATUS);
                        $iroOrder->setState(BatOrderStatus::COMPLETE_STATE);
                        $this->orderRepository->save($iroOrder);
                        if ($order->getReturnSwiftCode() == 10) {
                            $this->sendReturnClosedMessage($iroOrder);
                        } else {
                            $this->sendReturnCompletedMessage($iroOrder);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info(
                'Return completed message exception : '.$order->getIncrementId().$e->getMessage()
            );
        }
    }

    /**
     * Return default store id
     *
     * @return int
     * @throws LocalizedException
     */
    public function getDefaultStoreId()
    {
        $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }

    /**
     * Return admin store id
     *
     * @return int
     * @throws LocalizedException
     */
    public function getAdminStoreId()
    {
        $websiteId =  $this->storeManager->getWebsite('admin')->getId();
        return $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
    }

    /**
     * Reindex products
     *
     * @param array $productIds
     * @return void
     */
    public function reIndexProducts($productIds)
    {
        $ids = array_unique($productIds);
        if (!empty($productIds)) {
            $indexerCollection = $this->indexerCollectionFactory->create();
            foreach ($indexerCollection as $indexer) {
                $indexer->reindexList($productIds);
            }
        }
    }

    /**
     * Check if order pushed to eda
     *
     * @param int $orderId
     * @param int $maxFailuresAllowed
     * @param string $channel
     * @return bool
     */
    public function isOrderPushedToEda($orderId, $maxFailuresAllowed, $channel)
    {
        $showButton = false;
        $edaOrder = $this->getOrderForEdaUpdate($orderId, $channel);
        if ($edaOrder != '') {
            if (!$edaOrder->getOrderSent()) {
                if ($edaOrder->getFailureAttempts() >= $maxFailuresAllowed) {
                    $showButton = true;
                }
            }
        }
        return $showButton;
    }

    /**
     * Return IRO product list
     *
     * @return mixed
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getIroProductList()
    {
        $rootNodeId = $this->storeManager->getStore($this->getDefaultStoreId())->getRootCategoryId();
        $category = $this->categoryFactory->create()->load($rootNodeId);
        $productCollection = $category->getProductCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addFieldToFilter('pricetag_type', ['eq' => 0])
            ->addFieldToFilter('is_return', ['eq' => 1])
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE])
            ->setOrder('position', 'ASC');
        return $productCollection;
    }

    /**
     * Return customer store credit balance
     *
     * @param CustomerInterface $customer
     * @return Balance
     * @throws LocalizedException
     */
    public function getCustomerStoreCreditBalance($customer)
    {
        $balance = $this->_balanceFactory->create()
            ->setCustomerId($customer->getId())
            ->setWebsiteId($customer->getWebsiteId());
        return $balance->loadbyCustomer();
    }
}
