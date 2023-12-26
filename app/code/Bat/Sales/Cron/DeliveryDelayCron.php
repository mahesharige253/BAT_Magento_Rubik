<?php

namespace Bat\Sales\Cron;

use Bat\Sales\Model\DeliveryDelay;

/**
 * @class DeliveryDelayCron
 */
class DeliveryDelayCron
{
    /**
     * @var DeliveryDelay
     */
    private DeliveryDelay $deliveryDelay;

    /**
     * @param DeliveryDelay $deliveryDelay
     */
    public function __construct(
        DeliveryDelay $deliveryDelay
    ) {
        $this->deliveryDelay = $deliveryDelay;
    }

    /**
     * Check Delivery Delay Orders Based on Configured Days
     */
    public function execute()
    {
        $this->deliveryDelay->checkDeliveryDelayOrders();
    }
}
