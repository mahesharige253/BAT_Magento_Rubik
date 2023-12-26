<?php

namespace Bat\OrderProducts\Cron;

use Bat\OrderProducts\Model\OrderCancelMessage;

/**
 * @class CreateOrderEda
 * Cron to cancel orders
 */
class CancelOrder
{
    /**
     * @var OrderCancelMessage
     */
    private OrderCancelMessage $orderCancelMessage;

    /**
     * @param OrderCancelMessage $orderCancelMessage
     */
    public function __construct(
        OrderCancelMessage $orderCancelMessage
    ) {
        $this->orderCancelMessage = $orderCancelMessage;
    }

    /**
     * Check order payment deadline
     */
    public function execute()
    {
       $this->orderCancelMessage->cancelOrder();
    }
}
