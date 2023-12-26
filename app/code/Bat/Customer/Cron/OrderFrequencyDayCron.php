<?php
namespace Bat\Customer\Cron;

use Bat\Customer\Model\OrderFrequencyDay;

class OrderFrequencyDayCron
{

    /**
     * @var OrderFrequencyDay
     */
    protected $orderFrequencyDay;

    /**
     * Constructor
     * @param OrderFrequencyDay $orderFrequencyDay
     */
    public function __construct(
        OrderFrequencyDay $orderFrequencyDay
    ) {
        $this->orderFrequencyDay = $orderFrequencyDay;
    }

    /**
     * Kakao message send cron
     *
     * @return void
     */
    public function execute()
    {
        $this->orderFrequencyDay->addLog('Order Frequency Day reminder cron start');
        $this->orderFrequencyDay->sendSmsOrderDay();
        $this->orderFrequencyDay->addLog('Order Frequency Day reminder cron end');
    }
}
