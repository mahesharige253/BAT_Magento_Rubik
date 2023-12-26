<?php

namespace Bat\OrderProducts\Cron;

use Bat\OrderProducts\Model\PaymentReminderMessage;

/**
 * @class OrderPaymentReminder
 * Cron to send payment reminder
 */
class OrderPaymentReminder
{

     /**
      * @var PaymentReminderMessage
      */
    protected $paymentReminderMessage;

    /**
     * @param PaymentReminderMessage $paymentReminderMessage
     */
    public function __construct(
        PaymentReminderMessage $paymentReminderMessage
    ) {
        $this->paymentReminderMessage = $paymentReminderMessage;
    }

    /**
     * Check order payment reminder
     */
    public function execute()
    {
        $this->paymentReminderMessage->sendPaymentReminderMessage();
    }
}
