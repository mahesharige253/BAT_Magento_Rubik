<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\Rma\Plugin\Model\Sales\Order;

use Bat\Sales\Model\EdaOrderType;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\ResourceModel\Order\Handler\State as OrderState;
use Bat\Sales\Model\BatOrderStatus;

/**
 * @class State
 *
 * Update order type status
 */
class State
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Set order state and status
     *
     * @param OrderState $subject
     * @param OrderState $state
     * @param Order $order
     * @return $this
     */
    public function afterCheck(OrderState $subject, OrderState $state, Order $order)
    {
        $currentState = $order->getState();
        $currentStatus = $order->getStatus();
        $edaOrderState = $order->getAction();
        $edaOrderType = $order->getEdaOrderType();
        if ($currentState == Order::STATE_COMPLETE || $currentState == Order::STATE_CLOSED) {
            if ($edaOrderState != 'delivered' && $edaOrderState != '') {
                $shippedStatus = [
                    'at_pickup',
                    'in transit',
                    'out_for_delivery',
                    'shipment_created'
                ];
                if (in_array($edaOrderState, $shippedStatus)) {
                    $order = $this->updateDefaultOrderStatusProcessing($order, $edaOrderType);
                } elseif ($edaOrderState == 'failure') {
                    $order->setStatus(BatOrderStatus::DELIVERY_FAILED_STATUS);
                    $order->setState(Order::STATE_CLOSED);
                } else {
                    $order = $this->updateDefaultOrderStatusProcessing($order, $edaOrderType);
                }
            } elseif ($edaOrderState == 'delivered') {
                $order->setState(BatOrderStatus::COMPLETE_STATE);
                $order->setStatus(BatOrderStatus::COMPLETED_STATUS);
            } else {
                $order = $this->updateDefaultOrderStatusProcessing($order, $edaOrderType);
            }
        } else {
            if ($currentState == Order::STATE_PROCESSING) {
                if ($edaOrderType == EdaOrderType::ZREONE && $order->getStatus() == 'processing') {
                    $order->setState(Order::STATE_PROCESSING);
                    $order->setStatus(BatOrderStatus::RETURN_IN_PROGRESS_STATUS);
                }
            }
        }
        if ($edaOrderState == 'return_complete') {
            $order->setState(BatOrderStatus::COMPLETE_STATE);
            $order->setStatus(BatOrderStatus::COMPLETED_STATUS);
        }
        if ($edaOrderState == 'return_request_closed') {
            $order->setState(BatOrderStatus::COMPLETE_STATE);
            $order->setStatus(BatOrderStatus::RETURN_REQUEST_CLOSED);
        }
        return $this;
    }

    /**
     * Update default status after processing based on order type
     *
     * @param Order $order
     * @param string $edaOrderType
     * @return Order
     */
    public function updateDefaultOrderStatusProcessing($order, $edaOrderType)
    {
        if ($edaOrderType == EdaOrderType::ZREONE) {
            $order->setState(BatOrderStatus::COMPLETE_STATE);
            $order->setStatus(BatOrderStatus::RETURN_REQUEST_CLOSED);
        } elseif ($edaOrderType == EdaOrderType::IRO) {
            $order->setState(BatOrderStatus::PROCESSING_STATE);
            $order->setStatus(BatOrderStatus::RETURN_IN_PROGRESS_STATUS);
        }elseif ($edaOrderType == EdaOrderType::ZLOB) {
            $order->setState(BatOrderStatus::COMPLETE_STATE);
            $order->setStatus(BatOrderStatus::ZLOB_COMPLETE_STATUS);
        } else {
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus(Order::STATE_PROCESSING);
        }
        return $order;
    }
}
