<?php

namespace Bat\Sales\Cron;

use Bat\Sales\Helper\Data;
use Bat\Sales\Model\BatOrderStatus;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Bat\Sales\Model\SendOrderDetails;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Bat\Integration\Helper\Data as IntegrationHelper;

/**
 * @class CreateOrderEda
 * Cron to create orders in EDA
 */
class CreateOrderEda
{
    private const LOG_ENABLED_PATH = 'bat_integrations/bat_order/eda_order_log';
    private const MAX_FAILURES_ALLOWED_PATH = 'bat_integrations/bat_order/eda_order_max_failures_allowed';
    private const EDA_ORDER_ENDPOINT_PATH = 'bat_integrations/bat_order/eda_order_endpoint';

    /**
     * @var int
     */
    private $maxFailuresAllowed;

    /**
     * @var string
     */
    private $apiEndPoint;

    /**
     * @var boolean
     */
    private $logEnabled;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private OrderStatusHistoryRepositoryInterface $orderStatusRepository;

    /**
     * @var IntegrationHelper
     */
    private IntegrationHelper $integrationHelper;

    /**
     * @param SendOrderDetails $sendOrderDetails
     * @param Data $dataHelper
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(
        SendOrderDetails $sendOrderDetails,
        Data $dataHelper,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        IntegrationHelper $integrationHelper
    ) {
        $this->sendOrderDetails = $sendOrderDetails;
        $this->dataHelper = $dataHelper;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * Create Orders in EDA
     */
    public function execute()
    {
        $this->logEnabled = $this->dataHelper->getSystemConfigValue(self::LOG_ENABLED_PATH);
        try {
            $this->maxFailuresAllowed = $this->dataHelper->getSystemConfigValue(self::MAX_FAILURES_ALLOWED_PATH);
            $edaOrderCollection = $this->sendOrderDetails->getEdaOrderCollection($this->maxFailuresAllowed);
            if ($edaOrderCollection->count()) {
                $this->apiEndPoint = $this->dataHelper->getSystemConfigValue(self::EDA_ORDER_ENDPOINT_PATH);
                foreach ($edaOrderCollection as $edaOrder) {
                    $order = $this->orderRepository->get($edaOrder['order_id']);
                    if ($order->getEdaOrderType() == 'ZOR' && $order->getStatus()== 'canceled') {
                        $this->edaOrdersResource->delete($edaOrder);
                        continue;
                    }
                    $result = $this->processOrder($edaOrder, $order);
                    $returnOrderType = '';
                    if ($result) {
                        $edaOrder->setOrderSent(1);
                        $this->edaOrdersResource->save($edaOrder);
                        $returnOrderType = $order->getEdaOrderType();
                        if ($returnOrderType== 'IRO') {
                            $this->dataHelper->sendReturnInProgressMessage($order);
                        }
                        $comment = 'Updated order to EDA for Channel : '.$edaOrder['channel'];
                        $order->addCommentToStatusHistory($comment);
                        if ($order->getEdaOrderType() == 'ZOR' && $edaOrder['channel'] == 'SWIFTPLUS') {
                            if ($order->getSapOrderStatus() == '' && $order->getAction() == '') {
                                $order->setState(BatOrderStatus::PENDING_STATE)
                                    ->setStatus(BatOrderStatus::PREPARING_TO_SHIP_STATUS);
                            }
                        }
                        $order->save();
                    } else {
                        $failureAttempts = $edaOrder['failure_attempts'] + 1;
                        $edaOrder->setFailureAttempts($failureAttempts);
                        $this->edaOrdersResource->save($edaOrder);
                    }
                    if ($result && $returnOrderType == 'ZRE1' && $order != '') {
                        $this->dataHelper->checkAllReturnOrderSentToIntegration($order);
                    }
                }
            } else {
                if ($this->logEnabled) {
                    $this->dataHelper->logEdaOrderUpdateRequest(
                        '=============================================='
                    );
                    $this->dataHelper->logEdaOrderUpdateRequest('No orders to update');
                }
            }
        } catch (\Exception $e) {
            if ($this->logEnabled) {
                $this->dataHelper->logEdaOrderUpdateRequest($e->getMessage());
            }
        }
    }

    /**
     * Process order data to EDA
     *
     * @param OrderInterface $order
     * @param array $edaOrder
     * @return bool|false[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processOrder($edaOrder, $order)
    {
        $result = false;
        $orderId = $edaOrder['order_id'];
        $channel =  $edaOrder['channel'];
        if ($order) {
            try {
                if ($this->logEnabled) {
                    $this->dataHelper->logEdaOrderUpdateRequest(
                        '==============================================',
                    );
                    $this->dataHelper->logEdaOrderUpdateRequest('Request : OrderId - '.$orderId);
                }
                $orderData = json_encode($this->sendOrderDetails->formatOrderData($order, $channel));
                $status = $this->integrationHelper->postDataToEda($orderData, $this->apiEndPoint);
                $statusDecoded = json_decode($status, true);
                if (isset($statusDecoded['success']) && $statusDecoded['success']) {
                    $result = true;
                }
                if ($this->logEnabled) {
                    $this->dataHelper->logEdaOrderUpdateRequest($orderData);
                    $statusLog = ($result) ? 'success' : 'failure';
                    $this->dataHelper->logEdaOrderUpdateRequest('Response : '.$statusLog);
                    $this->dataHelper->logEdaOrderUpdateRequest($status);
                }
            } catch (\Throwable $e) {
                $result = false;
                if ($this->logEnabled) {
                    $this->dataHelper->logEdaOrderUpdateRequest('Exception occured :'.$e->getMessage());
                }
            }
        }
        return $result;
    }
}
