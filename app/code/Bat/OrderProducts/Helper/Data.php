<?php
namespace Bat\OrderProducts\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Server key config path
     */
    public const PAYMENT_DEADLINE_IN_DAYS= 'payment_deadline/general/payment_deadline';

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * Data Construct
     *
     * @param Context $context
     * @param TimezoneInterface $timezoneInterface
     */

    public function __construct(
        Context $context,
        TimezoneInterface $timezoneInterface
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->timezoneInterface = $timezoneInterface;
        parent::__construct($context);
    }

    /**
     * Get Payment deadline date
     *
     * @return string
     */
    public function getPaymentDeadline()
    {
        $orderCreatedTime = $this->timezoneInterface->date()->format('H:i:s');
        $orderCreatedAtDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $orderCreatedDayNumber = date('w', strtotime($orderCreatedAtDate));
        $paymentDeadlineInDays = 0;
        if ($this->getPaymentDeadlineDays() != '' && $this->getPaymentDeadlineDays() > 0) {
            $paymentDeadlineInDays = $this->getPaymentDeadlineDays() - 1;
        }
        if($orderCreatedDayNumber == 0 || $orderCreatedDayNumber == 6){
            $paymentDeadlineInDays = $this->getPaymentDeadlineDays();
        }
        $beforeElevenPm = strtotime('23:00:00');
        $deadlineDate = '';
        if ($beforeElevenPm >= strtotime($orderCreatedTime)) {
            $noOfDays = $paymentDeadlineInDays;
            $addedDay = " +".$noOfDays. "weekday";
            $deadlineDate = date('Y/m/d H:i:s', strtotime($orderCreatedAtDate. $addedDay));
        } else {
            $noOfDays = $paymentDeadlineInDays;
            $addedDay = " +".$noOfDays. "weekday";
            $deadlineDate = date('Y/m/d H:i:s', strtotime($orderCreatedAtDate. $addedDay));
        }
        return $deadlineDate;
    }

    /**
     * Get First Payment deadline date
     *
     * @return string
     */
    public function getFirstPaymentDeadline()
    {
        $orderCreatedTime = $this->timezoneInterface->date()->format('H:i:s');
        $orderCreatedAtDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');

        $orderCreatedDayNumber = $day = date('w', strtotime($orderCreatedAtDate));
        $paymentDeadlineInDays = 0;

        $beforeElevenPm = strtotime('23:00:00');
        $deadlineDate = '';
        $weekendDays = 0;
        if ($orderCreatedDayNumber == 6) {// 0 means Sunday and 6 means Saturday
            $weekendDays += 2;
        }
        if ($orderCreatedDayNumber == 0) {// 0 means Sunday and 6 means Saturday
            $weekendDays += 1;
        }

        if ($beforeElevenPm >= strtotime($orderCreatedTime)) {
            $noOfDays = $weekendDays + $paymentDeadlineInDays;
            $addedDay = " +".$noOfDays. " days";

            $deadlineDate = date('Y/m/d H:i:s', strtotime($orderCreatedAtDate. $addedDay));

        } else {
            $noOfDays = $weekendDays + $paymentDeadlineInDays + 1;
            $addedDay = " +".$noOfDays. " days";
            $deadlineDate = date('Y/m/d H:i:s', strtotime($orderCreatedAtDate. $addedDay));
        }
        return $deadlineDate;
    }

    /**
     * Get Config path
     *
     * @param string $path
     * @return string|int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Cancel Order Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logOrderCancel($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/CancelOrder.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Order Payment Reminder Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logPaymentReminder($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/OrderPaymentReminder.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Payment Deadline Days
     *
     * @return int
     */
    public function getPaymentDeadlineDays()
    {
        return $this->getConfig(self::PAYMENT_DEADLINE_IN_DAYS);
    }
}
