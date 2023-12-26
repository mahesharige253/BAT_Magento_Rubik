<?php

namespace Bat\SalesGraphQl\Model;

use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Model\AbstractModel;
use Bat\OrderProducts\Helper\Data;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class OrderPaymentDeadline extends AbstractModel
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

     /**
     * @var Data
     */
    protected $data;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @param OrderFactory $orderFactory
     * @param Data $data
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        OrderFactory $orderFactory,
        Data $data,
        TimezoneInterface $timezoneInterface
    ) {
        $this->orderFactory = $orderFactory;
        $this->data = $data;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Order Payment Deadline
     *
     * @param type $orderId
     * @return type
     */
    public function getPaymentDeadline($orderId)
    {
        $order = $this->orderFactory->create()->load($orderId);
        $orderCreatedAtDate = $order->getCreatedAt();

        $orderCreatedTime = $this->timezoneInterface->date($orderCreatedAtDate)->format('H:i:s');
        $orderCreatedAtDate = $this->timezoneInterface->date($orderCreatedAtDate)->format('Y-m-d H:i:s');
        $orderCreatedDayNumber = date('w', strtotime($orderCreatedAtDate));
        $paymentDeadlineInDays = 0;
        if ($this->data->getPaymentDeadlineDays() != '' && $this->data->getPaymentDeadlineDays() > 0) {
            $paymentDeadlineInDays = $this->data->getPaymentDeadlineDays() - 1;
        }
        if($orderCreatedDayNumber == 0 || $orderCreatedDayNumber == 6){
            $paymentDeadlineInDays = $this->data->getPaymentDeadlineDays();
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
        return  $deadlineDate;
    }

     /**
     * Order Payment Deadline
     *
     * @param type $orderId
     * @return type
     */
    public function getThankyouPagePaymentDeadline($orderId)
    {
        $order = $this->orderFactory->create()->load($orderId);
        $orderCreatedAtDate = $order->getCreatedAt();
        $orderCreatedTime = $this->timezoneInterface->date($orderCreatedAtDate)->format('H:i:s');
        $orderCreatedAtDate = $this->timezoneInterface->date($orderCreatedAtDate)->format('Y-m-d H:i:s');
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
        return  $deadlineDate;
    }
}
