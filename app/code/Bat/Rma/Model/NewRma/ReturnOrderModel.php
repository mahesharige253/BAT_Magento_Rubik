<?php

namespace Bat\Rma\Model\NewRma;

use Bat\Rma\Helper\Data as RmaHelper;
use Bat\Sales\Helper\Data as SalesHelper;
use Bat\Sales\Model\BatOrderStatus;
use Bat\Sales\Model\EdaOrderType;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Bat\Sales\Model\SendOrderDetails;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource\CollectionFactory as EdaOrdersCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Customer\Helper\Data as CustomerHelperData;

/**
 * @class ReturnOrderModel
 * Create Return Orders Model
 */
class ReturnOrderModel
{
    private const ReturnOrderReasonLabels = ['001' => 'Fresh', '201' => 'Old', '151' => 'Damage'];

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

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
     * @var OrderItemRepositoryInterface
     */
    private OrderItemRepositoryInterface $orderItemRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var RmaRepositoryInterface
     */
    private RmaRepositoryInterface $rmaRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private AddressRepositoryInterface $addressRepository;

    /**
     * @var OrderManagementInterface
     */
    private OrderManagementInterface $orderManagement;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quote;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var CustomerHelperData
     */
    protected CustomerHelperData $helperData;

    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @var ToOrderItem
     */
    private ToOrderItem $toOrderItem;

    /**
     * @var Quote
     */
    private Quote $quoteModel;

    /**
     * @var bool
     */
    public bool $returnOrderLogEnabled = false;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var RmaHelper
     */
    private RmaHelper $rmaHelper;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param EdaOrdersCollectionFactory $edaOrdersCollectionFactory
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param LoggerInterface $logger
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param DateTimeFactory $dateTimeFactory
     * @param RmaRepositoryInterface $rmaRepository
     * @param ProductRepositoryInterface $productRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param OrderManagementInterface $orderManagement
     * @param QuoteManagement $quoteManagement
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quote
     * @param KakaoSms $kakaoSms
     * @param CustomerHelperData $helperData
     * @param SalesHelper $salesHelper
     * @param ToOrderItem $toOrderItem
     * @param Quote $quoteModel
     * @param ScopeConfigInterface $scopeConfig
     * @param RmaHelper $rmaHelper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        EdaOrdersCollectionFactory $edaOrdersCollectionFactory,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        LoggerInterface $logger,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        DateTimeFactory $dateTimeFactory,
        RmaRepositoryInterface $rmaRepository,
        ProductRepositoryInterface $productRepository,
        AddressRepositoryInterface $addressRepository,
        OrderManagementInterface $orderManagement,
        QuoteManagement $quoteManagement,
        StoreManagerInterface $storeManager,
        QuoteFactory $quote,
        KakaoSms $kakaoSms,
        CustomerHelperData $helperData,
        SalesHelper $salesHelper,
        ToOrderItem $toOrderItem,
        Quote $quoteModel,
        ScopeConfigInterface $scopeConfig,
        RmaHelper $rmaHelper,
        SendOrderDetails $sendOrderDetails,
        ResourceConnection $resourceConnection
    ) {
        $this->customerRepository = $customerRepository;
        $this->edaOrdersCollectionFactory = $edaOrdersCollectionFactory;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->logger = $logger;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->rmaRepository = $rmaRepository;
        $this->productRepository = $productRepository;
        $this->addressRepository = $addressRepository;
        $this->orderManagement = $orderManagement;
        $this->quoteManagement = $quoteManagement;
        $this->storeManager = $storeManager;
        $this->quote = $quote;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->salesHelper = $salesHelper;
        $this->toOrderItem = $toOrderItem;
        $this->quoteModel = $quoteModel;
        $this->scopeConfig = $scopeConfig;
        $this->rmaHelper = $rmaHelper;
        $this->sendOrderDetails = $sendOrderDetails;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param array $rma
     * @param OrderInterface $order
     * @param string $returnReason
     */
    public function processReturnOrders($rma, $order, $returnReason)
    {
        $orderCreated = ['success'=>false,'order_ids' => []];
        $this->getReturnOrderLogEnabledStatus();
        $this->createReturnOrderLog('In Return order processReturnOrders function');
        $orders = ['001' => [], '201' => [], '151' => []];
        foreach ($rma as $rmaItem) {
            $this->createReturnOrderLog('In Return order processReturnOrders function rmaitem exists');
            $data = [];
            if ($rmaItem['fresh']) {
                $data['sku'] = $rmaItem['sku'];
                $data['qty'] = $rmaItem['fresh'];
                $data['order_reason'] = '001';
                $orders['001'][] = $data;
            }
            if ($rmaItem['old']) {
                $data['sku'] = $rmaItem['sku'];
                $data['qty'] = $rmaItem['old'];
                $data['order_reason'] = '201';
                $orders['201'][] = $data;
            }
            if ($rmaItem['damage']) {
                $data['sku'] = $rmaItem['sku'];
                $data['qty'] = $rmaItem['damage'];
                $data['order_reason'] = '151';
                $orders['151'][] = $data;
            }
        }
        $this->createReturnOrderLog('In Return order processReturnOrders function rmaitem process exit');
        $customer = $this->customerRepository->getById($order->getCustomerId());
        $this->createReturnOrderLog('In Return order processReturnOrders function rmaitem after load customer');
        try {
            $orderIds = $this->createNewOrder($orders, $returnReason, $customer, $order);
            if ($orderIds['success']) {
                $this->createReturnOrderLog('Return Orders created: '.json_encode($orderIds['order_id']));
                $orderCreated['success'] = true;
                $orderCreated['return_order_id'] = $orderIds['return_order_id'];
                $orderCreated['order_ids'] = $orderIds['order_id'];
                $orderIds = implode(', ', $orderIds['order_id']);
                $order->addCommentToStatusHistory(
                    __('Return Orders Created : '.$orderIds)
                )->setIsCustomerNotified(false);
                $order->setReturnOriginalOrderId($orderIds);
                $order->setIsReturnOrderCreated(1);
                $order = $order->save();
                $channels = ['SWIFTPLUS','OMS'];
                $returnOrderCount = count($orderCreated['return_order_id']);
                $i = 0;
                foreach ($orderCreated['return_order_id'] as $returnOrderId) {
                    $returnOrder = $this->orderRepository->get($returnOrderId);
                    foreach ($channels as $channel) {
                        $orderSent = $this->sendOrderDetails->processOrderSendToEda($returnOrder, $channel);
                        if($orderSent){
                            $i++;
                        }
                    }
                }
                $this->logger->info('Return order count sent to integration: '.$i);
                if ($i == ($returnOrderCount*2)) {
                    $order->setAction('return_complete');
                    $order->setStatus(BatOrderStatus::COMPLETED_STATUS);
                    $order->setState(BatOrderStatus::COMPLETE_STATE);
                    $order->save();
                    if($order->getReturnSwiftCode() == 10){
                        $this->salesHelper->sendReturnClosedMessage($order);
                    }else {
                        $this->salesHelper->sendReturnCompletedMessage($order);
                    }
                }
            } else {
                $orderCreated['success'] = false;
                $this->createReturnOrderLog($orderIds['message']);
            }
        } catch (\Exception $e) {
            $orderCreated['success'] = false;
            $this->createReturnOrderLog('Exception occurred : '.$e->getMessage());
            $this->logger->info('Return order create Exception :'.$e->getMessage());
        }
        return $orderCreated;
    }

    /**
     * Create return orders
     *
     * @param array $orders
     * @param string $returnReason
     * @param CustomerInterface $customer
     * @param OrderInterface $existingOrder
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createNewOrder($orders, $returnReason, $customer, $existingOrder)
    {
        $orderIds = ['success' => true ,'order_id' => [], 'message' => ''];
        $productIdsForStatusUpdate = [];
        try {
            $storeId = $customer->getStoreId();
            $adminStoreId = $this->salesHelper->getAdminStoreId();
            $zreOrderCreated = $this->checkIfZreOrderCreated($existingOrder);
            foreach ($orders as $type => $items) {
                $this->createReturnOrderLog('In Return order create loop');
                if (!empty($zreOrderCreated[$type])) {
                    $this->createReturnOrderLog('Return order for type : '.self::ReturnOrderReasonLabels[$type].
                        ' already created, Order Id : '.$zreOrderCreated[$type][0]);
                    $orderIds['return_order_id'][] = $zreOrderCreated[$type][1];
                    $orderIds['order_id'][] = $zreOrderCreated[$type][0];
                    continue;
                }
                if ($items) {
                    $this->createReturnOrderLog('In Return order create loop Item exists');
                    $quote = $this->quote->create();
                    $quote->setStoreId($this->salesHelper->getDefaultStoreId());
                    $quote->assignCustomer($customer);
                    foreach ($items as $item) {
                        $product = $this->productRepository->get($item['sku']);
                        $product->setStoreId($adminStoreId);
                        $productIdsForStatusUpdate[] = $product->getId();
                        $stockData = $product->getStockData();
                        $stockData['manage_stock'] = 0;
                        $stockData['use_config_manage_stock'] = 0;
                        $product->setStockData($stockData);
                        $product = $this->productRepository->save($product);
                        $quote->addProduct($product, $item['qty']);
                    }
                    $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
                    $shippingAddress = $quote->getShippingAddress()->setCustomerAddressId($shippingAddress->getId());
                    $shippingAddress->setCollectShippingRates(true)
                        ->collectShippingRates()
                        ->setShippingMethod('flatrate_flatrate');
                    $quote->setInventoryProcessed(true);
                    $quote->setUseCustomerBalance(false);
                    $quote->setEdaOrderType(EdaOrderType::ZREONE);
                    $quote->setIsReturnOrder(1);
                    $quote->collectTotals()->save();
                    if ($quote->getGrandTotal() == 0) {
                        $quote->setPaymentMethod('free');
                        $quote->getPayment()->importData(['method' => 'free']);
                    } else {
                        $quote->setPaymentMethod('banktransfer');
                        $quote->getPayment()->importData(['method' => 'banktransfer']);
                    }
                    $order = $this->quoteManagement->submit($quote);
                    $this->salesHelper->updateProduct($productIdsForStatusUpdate);
                    $order->setTotalPaid($order->getTotalDue());
                    $order->setTotalDue(0);
                    $order->setBaseTotalDue(0);
                    $order->setOrderType('Return Order');
                    $order->setReturnSwiftCode($returnReason);
                    $order->setReturnSwiftReason($type);
                    $order->setReturnOriginalOrderId($existingOrder->getIncrementId());
                    $this->orderRepository->save($order);
                    $this->salesHelper->updateOrderToEda(
                        $order->getEntityId(),
                        $order->getEdaOrderType(),
                        'SWIFTPLUS',
                        $order->getIncrementId()
                    );
                    $this->salesHelper->updateOrderToEda(
                        $order->getEntityId(),
                        $order->getEdaOrderType(),
                        'OMS',
                        $order->getIncrementId()
                    );
                    $orderIds['return_order_id'][] = $order->getEntityId();
                    $orderIds['order_id'][] = $order->getIncrementId();
                    $existingReturnOrderIds = $existingOrder->getReturnOriginalOrderId();
                    if ($existingReturnOrderIds != '') {
                        $existingReturnOrderIds = explode(', ', $existingReturnOrderIds);
                        $existingReturnOrderIds[] = $order->getIncrementId();
                        $existingReturnOrderIds = implode(', ', $existingReturnOrderIds);
                        $existingOrder->setReturnOriginalOrderId($existingReturnOrderIds);
                    } else {
                        $existingOrder->setReturnOriginalOrderId($order->getIncrementId());
                    }
                    $existingOrder = $existingOrder->save();
                }
            }
        } catch (\Exception $e) {
            $this->salesHelper->updateProduct($productIdsForStatusUpdate);
            $orderIds['success'] = false;
            $orderIds['message'] = $e->getMessage();
        }
        $this->createReturnOrderLog('In Return order exit orders created '.json_encode($orderIds));
        return $orderIds;
    }

    /**
     * Return order creation Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function createReturnOrderLog($message)
    {
        if ($this->returnOrderLogEnabled) {
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaCreateReturnOrder.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($message);
        }
    }

    /**
     * set return order log enabled status
     */
    public function getReturnOrderLogEnabledStatus()
    {
        $this->returnOrderLogEnabled = $this->scopeConfig->getValue('bat_integrations/bat_order/eda_return_order_log');
    }

    /**
     * Check If ZRE Order created
     *
     * @param OrderInterface $existingOrder
     */
    public function checkIfZreOrderCreated($existingOrder)
    {
        $actualReturnOrders = ['001' => [], '201' => [], '151' => []];
        $existingReturnOrderIds = $existingOrder->getReturnOriginalOrderId();
        if ($existingReturnOrderIds != '') {
            $connection = $this->resourceConnection->getConnection();
            $table = $connection->getTableName('sales_order');
            $existingReturnOrderIds = explode(', ', $existingReturnOrderIds);
            foreach ($existingReturnOrderIds as $returnOrderId) {
                $query = $connection->select()->from($table, ['increment_id','entity_id', 'return_swift_reason'])
                    ->where("increment_id = ?", $returnOrderId);
                $result = $connection->fetchAll($query);
                if (count($result)) {
                    foreach ($result as $orderDetails) {
                        $returnSwiftReason = $orderDetails['return_swift_reason'];
                        if ($returnSwiftReason == '001') {
                            $actualReturnOrders['001'][0] = $orderDetails['increment_id'];
                            $actualReturnOrders['001'][1] = $orderDetails['entity_id'];
                        }
                        if ($returnSwiftReason == '201') {
                            $actualReturnOrders['201'][0] = $orderDetails['increment_id'];
                            $actualReturnOrders['201'][1] = $orderDetails['entity_id'];
                        }
                        if ($returnSwiftReason == '151') {
                            $actualReturnOrders['151'][0] = $orderDetails['increment_id'];
                            $actualReturnOrders['151'][1] = $orderDetails['entity_id'];
                        }
                    }
                }
            }
        }
        return $actualReturnOrders;
    }
}
