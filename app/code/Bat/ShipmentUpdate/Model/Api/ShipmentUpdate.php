<?php

namespace Bat\ShipmentUpdate\Model\Api;

use Bat\Sales\Helper\Data;
use Bat\Sales\Model\BatOrderStatus;
use Bat\Sales\Model\EdaOrderType;
use Bat\ShipmentUpdate\Api\OrderShipmentUpdateInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;

class ShipmentUpdate implements OrderShipmentUpdateInterface
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private \Magento\Sales\Model\Order $order;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param OrderRepositoryInterface $orderRepository
     * @param EventManager $eventManager
     * @param Data $dataHelper
     */
    public function __construct(
        \Magento\Sales\Model\Order $order,
        OrderRepositoryInterface $orderRepository,
        EventManager $eventManager,
        Data $dataHelper
    ) {
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->dataHelper = $dataHelper;
    }
    /**
     * @inheritdoc
     */
    public function shipmentUpdate($entity)
    {
        $this->addLog("====================================================");
        $this->addLog("Request");
        $this->addLog(json_encode($entity));
        try {
            $this->validateInput($entity);
            $orderId = $entity['order_increment_id'];
            $data = [];
            $orderData = $this->order->loadByIncrementId($orderId);
            if (!$orderData->getId()) {
                $orderCollection = $this->dataHelper->getOrderOnSapOrderNumber($orderId);
                if ($orderCollection->getSize()) {
                    $orderData = $orderCollection->getFirstItem();
                } else {
                    throw new LocalizedException(__('Order not found'));
                }
            }
            if ($orderData->getId()) {
                $order = $this->orderRepository->get($orderData->getId());
                $order->setMessageId($entity['message_id']);
                $order->setMessageDate($entity['message_date']);
                $order->setCarrierCode($entity['carrier_code']);
                $order->setCarrierName($entity['carrier_name']);
                $order->setAwbNumber($entity['awb_number']);
                $order->setTrackingUrl($entity['tracking_url']);
                $order->setShippingStatusCode($entity['shipping_status_code']);
                $order->setShippingStatusMessage($entity['shipping_status_message']);
                $order->setShipDate($entity['ship_date']);
                $orderType = $order->getEdaOrderType();
                if (!$order->getIsShipmentAvailable() &&
                    ($orderType == EdaOrderType::ZOR || $orderType == EdaOrderType::ZLOB)
                ) {
                    if ($order->getSapOrderStatus() != '000') {
                        throw new LocalizedException(__('Order not confirmed'));
                    }
                    $comment = 'Shipment Created';
                    $success = $this->dataHelper->createInvoice($order, $comment);
                    if ($success) {
                        $smsSent = $this->dataHelper->sendShipmentStartedSms($order, $entity['awb_number']);
                        if (!$smsSent['success']) {
                            $this->addLog($smsSent['message']);
                        }
                    } else {
                        throw new LocalizedException(__('Something went wrong could not create shipment'));
                    }
                } else {
                    $order->setIsShipmentAvailable(1);
                    $order->addCommentToStatusHistory(
                        __('Shipment updated')
                    )->setIsCustomerNotified(false);
                    $order->save();
                }
            }
            $Updatedorder = $this->orderRepository->get($orderData->getId());
            $data['status'] = true;
            $data['message_id'] = $Updatedorder->getMessageId();
            $data['message_date'] = $Updatedorder->getMessageDate();
            $data['order_id'] = $Updatedorder->getIncrementId();
            $data['courier_id'] = $Updatedorder->getCarrierCode();
            $data['courier_name'] = $Updatedorder->getCarrierName();
            $data['shipment_invoice'] = $Updatedorder->getAwbNumber();
            $data['track_url'] = $Updatedorder->getTrackingUrl();
            $data['status_cd'] = $Updatedorder->getShippingStatusCode();
            $data['status_text'] = $Updatedorder->getShippingStatusMessage();
            $data['wms_out_date'] = $Updatedorder->getShipDate();
        } catch (\Exception $e) {
            $data['status'] = false;
            $data['message'] = $e->getMessage();
        }
        $this->addLog("Response");
        $this->addLog(json_encode($data));
        $return['response'] = $data;
        return $return;
    }

    /**
     * Add Log Function
     *
     * @param mixed $logData
     * @param string $filename
     */
    public function addLog($logData, $filename = "order_shipment_update.log")
    {
        if ($this->canWriteLog($filename)) {
            $this->logger->info($logData);
        }
    }

     /**
      * Write logfile
      *
      * @param string $filename
      */
    protected function canWriteLog($filename)
    {
        $logEnable = 1;
        if ($logEnable) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/'.$filename);
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $this->logger = $logger;
        }

        return $logEnable;
    }

    /**
     * Validate Input
     *
     * @param mixed $entity
     * @throws LocalizedException
     */
    public function validateInput($entity)
    {
        $errorMsg = [];
        if (!isset($entity['message_id']) || (trim($entity['message_id']) == '')) {
            $errorMsg[] = 'message_id is required and value should be specified';
        }
        if (!isset($entity['message_date']) || (trim($entity['message_date']) == '')) {
            $errorMsg[] = 'message_date is required and value should be specified';
        }
        if (!isset($entity['order_increment_id']) || (trim($entity['order_increment_id']) == '')) {
            $errorMsg[] = 'order_increment_id is required and value should be specified';
        }
        if (!isset($entity['carrier_name']) || (trim($entity['carrier_name']) == '')) {
            $errorMsg[] = 'carrier_name is required and value should be specified';
        }
        if (!isset($entity['carrier_code']) || (trim($entity['carrier_code']) == '')) {
            $errorMsg[] = 'carrier_code is required and value should be specified';
        }
        if (!isset($entity['awb_number']) || (trim($entity['awb_number']) == '')) {
            $errorMsg[] = 'awb_number is required and value should be specified';
        }
        if (!isset($entity['tracking_url']) || (trim($entity['tracking_url']) == '')) {
            $errorMsg[] = 'tracking_url is required and value should be specified';
        }
        if (!isset($entity['ship_date']) || (trim($entity['ship_date']) == '')) {
            $errorMsg[] = 'ship_date is required and value should be specified';
        }
        if (!empty($errorMsg)) {
            throw new LocalizedException(__(implode(', ', $errorMsg)));
        }
    }
}
