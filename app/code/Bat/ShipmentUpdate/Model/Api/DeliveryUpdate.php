<?php

namespace Bat\ShipmentUpdate\Model\Api;

use Bat\Customer\Helper\Data as CustomerHelperData;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Sales\Helper\Data;
use Bat\Sales\Model\EdaOrderType;
use Bat\ShipmentUpdate\Api\OrderDeliveryUpdateInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\Order;
use Bat\Sales\Model\BatOrderStatus;
use Bat\Rma\Model\NewRma\NewRmaModel;
use Bat\ShipmentUpdate\Model\EdaOrderFailedFactory;
use Bat\ShipmentUpdate\Model\ResourceModel\EdaOrderFailed\CollectionFactory as EdaOrderFailedCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Directory\Model\Currency;
use Magento\Framework\Currency\Data\Currency as CurrencyData;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;

/**
 * @class DeliveryUpdate
 * Update delivery status
 */
class DeliveryUpdate implements OrderDeliveryUpdateInterface
{
    /**
     * @var Order
     */
    private Order $order;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var CustomerHelperData
     */
    private CustomerHelperData $helperData;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var NewRmaModel
     */
    private NewRmaModel $newRmaModel;

    /**
     * @var EdaOrderFailedFactory
     */
    private $edaOrderFailedFactory;

    /**
     * @var EdaOrderFailedCollection
     */
    private EdaOrderFailedCollection $edaOrderFailedCollection;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var Currency
     */
    protected Currency $currency;

    /**
     * @var GetDiscountMessage
     */
    protected GetDiscountMessage $getDiscountMessage;

    /**
     * @param Order $order
     * @param OrderRepositoryInterface $orderRepository
     * @param EventManager $eventManager
     * @param Data $dataHelper
     * @param KakaoSms $kakaoSms
     * @param CustomerHelperData $helperData
     * @param CompanyManagementInterface $companyManagement
     * @param NewRmaModel $newRmaModel
     * @param EdaOrderFailedFactory $edaOrderFailedFactory
     * @param EdaOrderFailedCollection $edaOrderFailedCollection
     * @param ResourceConnection $resourceConnection
     * @param Currency $currency
     * @param GetDiscountMessage $getDiscountMessage
     */
    public function __construct(
        Order $order,
        OrderRepositoryInterface $orderRepository,
        EventManager $eventManager,
        Data $dataHelper,
        KakaoSms $kakaoSms,
        CustomerHelperData $helperData,
        CompanyManagementInterface $companyManagement,
        NewRmaModel $newRmaModel,
        EdaOrderFailedFactory $edaOrderFailedFactory,
        EdaOrderFailedCollection $edaOrderFailedCollection,
        ResourceConnection $resourceConnection,
        Currency $currency,
        GetDiscountMessage $getDiscountMessage
    ) {
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->dataHelper = $dataHelper;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->companyManagement = $companyManagement;
        $this->newRmaModel = $newRmaModel;
        $this->edaOrderFailedFactory = $edaOrderFailedFactory;
        $this->edaOrderFailedCollection = $edaOrderFailedCollection;
        $this->resourceConnection = $resourceConnection;
        $this->currency = $currency;
        $this->getDiscountMessage = $getDiscountMessage;
    }

    /**
     * Update delivery status
     *
     * @param mixed $data
     * @return array[]
     */
    public function deliveryUpdate($data)
    {
        $result = ['success' => false, 'message' => ''];
        try {
            $this->addLog("====================================================");
            $this->addLog("Request : ");
            $this->addLog(json_encode($data));
            $this->addLog("Response : ");
            $this->validateInput($data);
            $orderData = $this->order->loadByIncrementId($data['order_increment_id']);
            if (!$orderData->getId()) {
                $orderCollection = $this->dataHelper->getOrderOnSapOrderNumber($data['order_increment_id']);
                if ($orderCollection->getSize()) {
                    $orderData = $orderCollection->getFirstItem();
                } else {
                    throw new LocalizedException(__('Order not found'));
                }
            }
            $order = $this->orderRepository->get($orderData->getId());
            $orderType = $order->getEdaOrderType();
            if ($orderType == EdaOrderType::ZREONE || $orderType == EdaOrderType::IRO) {
                throw new LocalizedException(__($orderType.' '.'order cannot be updated for delivery'));
            }
            if ($order->getAction() == 'failure') {
                throw new LocalizedException(__('Incomplete order cannot be updated'));
            }
            $order = $this->setDeliveryInformation($order, $data);
            $sapOrderStatus = $order->getSapOrderStatus();
            if ($order->getStatus() == BatOrderStatus::COMPLETED_STATUS) {
                throw new LocalizedException(__('Completed order cannot be updated'));
            }
            if ($order->getStatus() == BatOrderStatus::DELIVERY_FAILED_STATUS) {
                throw new LocalizedException(__('Incomplete order cannot be updated'));
            }
            if (!$order->hasInvoices() || $sapOrderStatus != '000') {
                throw new LocalizedException(__('Shipment not created'));
            }
            $action = $data['action'];
            if ($action == 'delivered') {
                $order->setState(BatOrderStatus::COMPLETE_STATE);
                $order->setStatus(BatOrderStatus::COMPLETED_STATUS);
            } else {
                if ($action == 'failure') {
                    $orderItems = $order->getAllItems();
                    $order = $this->orderRepository->get($orderData->getId());
                    $order = $this->setDeliveryInformation($order, $data);
                    $order->setStatus(BatOrderStatus::DELIVERY_FAILED_STATUS);
                    $order->setState(BatOrderStatus::CLOSED_STATE);
                    $this->addDeliveryFailureOrder($order->getEntityId(), $order->getCustomerId());
                    if($order->getDiscountAmount() != '' && $order->getAppliedRuleIds() != ''){
                        $this->getDiscountMessage->setCustomerTimesUsed($order->getCustomerId(), $order->getAppliedRuleIds());
                    }
                } else {
                    if ($order->getEdaOrderType() == EdaOrderType::ZREONE) {
                        $order->setStatus(BatOrderStatus::RETURN_IN_PROGRESS_STATUS);
                    } else {
                        $order->setStatus(BatOrderStatus::SHIPPED_STATUS);
                    }
                    $order->setState(BatOrderStatus::PROCESSING_STATE);
                }
            }
            $order->addCommentToStatusHistory(
                __('Delivery updated : '.$action)
            )->setIsCustomerNotified(false);
            $this->isOrderDelivered($order->getEntityId());
            $order->save();
            $result['success'] = true;
            $result['message'] = 'Delivery status updated successfully';
        } catch (LocalizedException $e) {
            $result['message'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
            $result['message'] = 'Something went wrong';
        }
        $this->addLog(json_encode($result));
        return [$result];
    }

    /**
     * Set Delivery Information
     *
     * @param OrderInterface $order
     * @param mixed $data
     */
    public function setDeliveryInformation($order, $data)
    {
        $order->setMessageId($data['message_id']);
        $order->setMessageDate($data['message_date']);
        if (isset($data['carrier_code']) && $data['carrier_code'] != '') {
            $order->setCarrierCode($data['carrier_code']);
        }
        $order->setCarrierName($data['carrier_name']);
        $order->setAwbNumber($data['awb_number']);
        $order->setTrackingUrl($data['trackin_url']);
        $order->setAction($data['action']);
        $order->setActionDate($data['action_date']);
        $order->setActionTime($data['action_time']);
        $order->setActionLocal($data['action_local']);
        $order->setCountryCode($data['country_code']);
        return $order;
    }

    /**
     * Check valid order status
     *
     * @param $action
     * @return bool
     */
    public function isValidOrderStatus($action)
    {
        $statuses = [
            'at_pickup',
            'failure',
            'in_transit',
            'out_for_delivery',
            'delivered'
        ];
        if (in_array($action, $statuses)) {
            return true;
        }
        return false;
    }

    /**
     * Delivery update Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addlog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaDeliveryUpdate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Validate Input
     *
     * @param mixed $data
     * @throws LocalizedException
     */
    public function validateInput($data)
    {
        $errorMsg = [];
        if (!isset($data['message_id']) || (trim($data['message_id']) == '')) {
            $errorMsg[] = 'message_id is required and value should be specified';
        }
        if (!isset($data['message_date']) || (trim($data['message_date']) == '')) {
            $errorMsg[] = 'message_date is required and value should be specified';
        }
        if (!isset($data['order_increment_id']) || (trim($data['order_increment_id']) == '')) {
            $errorMsg[] = 'Order Number is required and value should be specified';
        }
        if (!isset($data['carrier_name']) || (trim($data['carrier_name']) == '')) {
            $errorMsg[] = 'carrier_name is required and value should be specified';
        }
        if (!isset($data['awb_number']) || (trim($data['awb_number']) == '')) {
            $errorMsg[] = 'awb_number is required and value should be specified';
        }
        if (!isset($data['action']) || (trim($data['action']) == '')) {
            $errorMsg[] = 'action is required and value should be specified';
        } else {
            if (!$this->isValidOrderStatus($data['action'])) {
                $errorMsg[] = 'Allowed values for action is at_pickup,failure,in_transit,out_for_delivery,delivered';
            }
        }
        if (!isset($data['action_local']) || (trim($data['action_local']) == '')) {
            $errorMsg[] = 'action_local is required and value should be specified';
        }
        if (!isset($data['action_time']) || (trim($data['action_time']) == '')) {
            $errorMsg[] = 'action_time is required and value should be specified';
        }
        if (!isset($data['action_date']) || (trim($data['action_date']) == '')) {
            $errorMsg[] = 'action_date is required and value should be specified';
        }
        if (!isset($data['trackin_url']) || (trim($data['trackin_url']) == '')) {
            $errorMsg[] = 'trackin_url is required and value should be specified';
        }
        if (!isset($data['country_code']) || (trim($data['country_code']) == '')) {
            $errorMsg[] = 'country_code is required and value should be specified';
        }
        if (!empty($errorMsg)) {
            throw new LocalizedException(__(implode(', ', $errorMsg)));
        }
    }

    /**
     * Send sms to customer on delivery completion
     *
     * @param OrderInterface $order
     */
    public function sendDeliveryCompletedMessage($order)
    {
        try {
            $totalQty = 0;
            $firstItemName = '';
            $i= 0;
            $totalAmount = 0;
            foreach ($order->getAllItems() as $item) {
                $firstItemName = $item->getName();
                $totalQty = $totalQty + $item->getQtyOrdered();
                $i++;
                $totalAmount = $totalAmount +
                    ($item->getRowTotal() - $item->getDiscountAmount()) + $item->getTaxAmount();
            }
            if ($i > 1) {
                $firstItemName = $firstItemName.' 외 '.(--$i).' 개';
            }
            /** @var CustomerInterface $customer */
            $customer = $this->helperData->getCustomerById($order->getCustomerId());
            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            $params = [];
            if ($order->getEdaOrderType() == EdaOrderType::ZREONE) {
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
                    'totalreturn_amount' => $this->currency->format($totalAmount, ['display'=> CurrencyData::NO_SYMBOL, 'precision' => 0], false),
                    'outlet_name' => $outletName
                ];
                $this->kakaoSms->sendSms($mobileNumber, $params, $templateCode);
            }
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
        }
    }

    /**
     * Add delivery failure order for ZLOB order create
     *
     * @param int $orderId
     * @param int $customerId
     * @throws \Exception
     */
    public function addDeliveryFailureOrder($orderId, $customerId)
    {
        $zlobOrderAdded = $this->getDeliveryFailureOrder($orderId);
        if (!$zlobOrderAdded) {
            $dataModel = $this->edaOrderFailedFactory->create();
            $dataModel->setData([
                'customer_id' => $customerId,
                'order_id' => $orderId
            ]);
            $dataModel->save();
        } else {
            throw new LocalizedException(__('Incomplete order cannot be updated'));
        }
    }

    /**
     * Check delivery failure order added for create
     *
     * @param int $orderId
     * @return bool
     */
    public function getDeliveryFailureOrder($orderId)
    {
        $zlobOrders = $this->edaOrderFailedCollection->create();
        $zlobOrders->addFieldToFilter('order_id', ['eq' => $orderId]);
        if ($zlobOrders->getSize()) {
            return true;
        }
        return false;
    }

    /**
     * Check if Order is delivered
     *
     * @param $orderId
     * @throws LocalizedException
     */
    public function isOrderDelivered($orderId)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('sales_order');
        $query = $connection->select()->from($table, ['action', 'status'])->where("entity_id = ?", $orderId);
        $result = $connection->fetchAll($query);
        if (count($result)) {
            foreach ($result as $orderDetails) {
                if ($orderDetails['status'] == 'complete' || $orderDetails['action'] == 'delivered') {
                    throw new LocalizedException(__('Completed order cannot be updated'));
                }
                if ($orderDetails['status'] == 'incomplete' || $orderDetails['action'] == 'failure') {
                    throw new LocalizedException(__('Incomplete order cannot be updated'));
                }
            }
        }
    }
}
