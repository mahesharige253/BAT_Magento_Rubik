<?php

namespace Bat\ShipmentUpdate\Cron;

use Bat\Sales\Helper\Data;
use Bat\Sales\Model\SendOrderDetails;
use Bat\ShipmentUpdate\Model\EdaOrderFailedFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @class OrderDeliveryFailed
 * Cron to New Order Discount
 */
class OrderDeliveryFailed
{
    /**
     * @var Data
     */
    private Data $data;

    /**
     * @var EdaOrderFailedFactory
     */
    private $edaOrderFailedFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @param Data $data
     * @param EdaOrderFailedFactory $edaOrderFailedFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param SendOrderDetails $sendOrderDetails
     */
    public function __construct(
        Data $data,
        EdaOrderFailedFactory $edaOrderFailedFactory,
        OrderRepositoryInterface $orderRepository,
        SendOrderDetails $sendOrderDetails
    ) {
        $this->data = $data;
        $this->edaOrderFailedFactory = $edaOrderFailedFactory;
        $this->orderRepository = $orderRepository;
        $this->sendOrderDetails = $sendOrderDetails;
    }
    /**
     * Check Delivery Failed order
     */
    public function execute()
    {
        $cronEnabled = $this->data->getSystemConfigValue(
            'payment_deadline/bat_order_delivery_failed/order_failed_cron_enable'
        );
        $this->addlog('=====================================');
        if (!$cronEnabled) {
            $this->addlog('ZLOB Order Create Cron is Disabled');
            return;
        }
        $collection = $this->edaOrderFailedFactory->create()->getCollection();
        $collection->addFieldToFilter('order_created', ['eq'=>0]);
        $currentTime = date('H:i:s');
        $cenvertedTime = date('Y-m-d H:i:s', strtotime(' -5 minutes '));
        $records = $collection->getData();
        if (count($records) <= 0) {
            $this->addlog('No orders to create');
        }
        foreach ($records as $record) {
            $this->addlog('Order Id : '.$record['order_id']);
            if ($record['created_at'] < $cenvertedTime) {
                try {
                    $model = $this->edaOrderFailedFactory->create();
                    $data = $model->load($record['entity_id']);
                    $order = $this->orderRepository->get($data->getOrderId());
                    if ($order->getReturnOriginalOrderId() == '') {
                        $orderItems = $order->getAllItems();
                        $deliveryFailedOrder = $this->data->createOrderForDeliveryFailure($order, $orderItems);
                        if ($deliveryFailedOrder['success']) {
                            $data->setOrderCreated(1);
                            $data->save();
                            $deliveryFailOrderId = $deliveryFailedOrder['order_id'];
                            $this->addlog('Order Created - Order Id : '.$deliveryFailOrderId);
                            $deliveryOrder = $this->orderRepository->get($deliveryFailOrderId);
                            $this->sendOrderDetails->processOrderSendToEda($deliveryOrder, 'OMS');
                        }
                    } else {
                        $this->addlog('Order Already Created - Order Id : '.$order->getReturnOriginalOrderId());
                        $data->setOrderCreated(1);
                        $data->save();
                    }
                } catch (\Exception $e) {
                    $this->addLog($e->getMessage());
                }
            } else {
                $this->addlog('Order create Time not in range');
            }
        }
    }

     /**
      * Delivery failed Log
      *
      * @param string $message
      * @throws Zend_Log_Exception
      */
    public function addlog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaDeliveryFailedOrders.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
