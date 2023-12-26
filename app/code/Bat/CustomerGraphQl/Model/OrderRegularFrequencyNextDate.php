<?php
namespace Bat\CustomerGraphQl\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\CustomerGraphQl\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyData;
use Bat\CustomerGraphQl\Model\OrderNextDate;

class OrderRegularFrequencyNextDate
{
     /**
      * @var TimezoneInterface
      */
    private $timezoneInterface;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Session
     */
    private $_session;

    /**
     * @var CollectionFactory
     */
    private $_orderFactory;

    /**
     * @var OrderFrequencyData
     */
    private $orderFrequencyData;

    /**
     * @var OrderNextDate
     */
    private $orderNextDate;

    /**
     * Construct method
     *
     * @param TimezoneInterface $timezoneInterface
     * @param Data $helper
     * @param Session $session
     * @param CollectionFactory $orderFactory
     * @param OrderFrequencyData $orderFrequencyData
     * @param OrderNextDate $orderNextDate
     */
    public function __construct(
        TimezoneInterface $timezoneInterface,
        Data $helper,
        Session $session,
        CollectionFactory $orderFactory,
        OrderFrequencyData $orderFrequencyData,
        OrderNextDate $orderNextDate
    ) {
        $this->timezoneInterface = $timezoneInterface;
        $this->helper = $helper;
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->orderFrequencyData = $orderFrequencyData;
        $this->orderNextDate = $orderNextDate;
    }

    /**
     * Get Closest date among all available date
     *
     * @param string $customer
     * @return string
     */
    public function getClosestDate($customer)
    {
        $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();
        $isTimeAllowToOrder = $this->orderFrequencyData->isTimeAllowOrder($customer);
        $orderFrequencyDay = '';
        if ($customer->getCustomAttribute('order_frequency_day')) {
            $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();
            $orderFrequencyTimeFrom = $customer->getCustomAttribute('order_frequency_time_from')->getValue();
            $frequencyTimeFrom = date('h:i:s A', strtotime($orderFrequencyTimeFrom));

            $orderFrequencyTimeTo = $customer->getCustomAttribute('order_frequency_time_to')->getValue();
            $frequencyTimeTo = date('h:i:s A', strtotime($orderFrequencyTimeTo));
        }
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
        $currentTime = date('h:i:s A');
        $currentDay = $this->timezoneInterface->date()->format('l');
        $dateArray = [];

        if ($orderFrequency == 0) {
            $orderPlaced = count($this->orderNextDate->getWeekly());
            $totalOrder = $this->helper->getFrequencyWeekly();
            if (($this->orderFrequencyData->allowDayTime($customer) == 1) && ($totalOrder - $orderPlaced > 0)){
                return $currentDate;
            } else {
                $nextDate = date('Y-m-d', strtotime("next ". $orderFrequencyDay));
                if($currentDate == $nextDate) {
                    $nextDate = date('Y-m-d', strtotime("second ". $orderFrequencyDay));
                }
                return $nextDate;
            }
        }

        if ($orderFrequency == 1) {
            $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();
            $isDayExistInFirstWeek = $this->checkDayExistInFirstWeekDay($orderFrequencyDay, $currentDate);
            $currentDateWeek = $this->orderFrequencyData->weekOfMonth($currentDate);
            $currentWeekEvenOrOdd = $this->orderFrequencyData->getEvenOdd($currentDateWeek);

            if($customer->getCustomAttribute('order_frequency_week')) {
               $orderFrequencyWeek = $customer->getCustomAttribute('order_frequency_week')->getValue();

                if($currentDate <= date('Y-m-d', strtotime($orderFrequencyDay))) {
                   $next = date('Y-m-d', strtotime($orderFrequencyDay));
                    $newDateWeek = $this->orderFrequencyData->weekOfMonth($next);
                   if($isDayExistInFirstWeek == 1) {
                        $newDateWeek += 1;
                   }
                    $newWeekEvenOrOdd = $this->orderFrequencyData->getEvenOdd($newDateWeek);
                    if($currentWeekEvenOrOdd != $orderFrequencyWeek) {
                       if($orderFrequencyDay == $currentDay) {
                        $next = date('Y-m-d', strtotime('second '.$orderFrequencyDay));
                       }else{
                            $next = date('Y-m-d', strtotime('next '.$orderFrequencyDay));
                       }
                    } else {
                        if(($currentDate == date('Y-m-d', strtotime($orderFrequencyDay))) && ($isTimeAllowToOrder == true) ) {
                            if(($currentWeekEvenOrOdd == $orderFrequencyWeek) && ($this->orderFrequencyData->allowDayTime($customer) == 1)) {
                                $next = date('Y-m-d', strtotime($orderFrequencyDay));
                            }else {
                                $next = date('Y-m-d', strtotime('second '.$orderFrequencyDay));
                            }
                        }else if(($currentDate == date('Y-m-d', strtotime($orderFrequencyDay)) && ($isTimeAllowToOrder != true) )) {
                             $next = date('Y-m-d', strtotime('second '.$orderFrequencyDay));
                        }else if($currentDate > date('Y-m-d', strtotime($orderFrequencyDay))) {
                            $next = date('Y-m-d', strtotime($orderFrequencyDay));
                        }
                    }
                    if(($newWeekEvenOrOdd != $orderFrequencyWeek) && ($currentDate != date('Y-m-d', strtotime($orderFrequencyDay)))) {
                        $next = date('Y-m-d', strtotime('second '.$orderFrequencyDay));
                    }
                }else if($currentDate >= date('Y-m-d', strtotime($orderFrequencyDay))) {
                    if($currentWeekEvenOrOdd == $orderFrequencyWeek) {
                        $next = date('Y-m-d', strtotime('second '.$orderFrequencyDay));
                    } else {
                        $next = date('Y-m-d', strtotime('next '.$orderFrequencyDay));
                    }
                }
            }
            return $next;
        }

        if ($orderFrequency == 2) {
            $currentDateWeek = $this->orderFrequencyData->weekOfMonth($currentDate);
            $orderFrequencyMonth = $customer->getCustomAttribute('order_frequency_month')->getValue();
            $orderFrequencyWeek = $customer->getCustomAttribute('order_frequency_week')->getValue();

            $allowedMonthlyWeek = $this->orderFrequencyData->getMonthlyWeek($orderFrequencyWeek);
            $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();

            $startDate = $this->timezoneInterface->date()->format('Y-m-01');
            $endDate = $this->timezoneInterface->date()->format('Y-m-t');

            return $nextOrderDayBiMonthly = $this->orderNextDate->getFrequencyDate($startDate, $endDate, $allowedMonthlyWeek, $orderFrequencyDay, $currentDate);
        }

        if ($orderFrequency == 3) {
            $sameMonth = 0;
            $currentDateWeek = $this->orderFrequencyData->weekOfMonth($currentDate);
            $orderFrequencyMonth = $customer->getCustomAttribute('order_frequency_month')->getValue();
            $orderFrequencyWeek = $customer->getCustomAttribute('order_frequency_week')->getValue();

            $allowedMonthlyWeek = $this->orderFrequencyData->getMonthlyWeek($orderFrequencyWeek);
            $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();

            $currentMonth =  date("m", strtotime($currentDate));
            $currentMonthEvenOrOdd = $this->orderFrequencyData->getEvenOdd($currentMonth);

            $startDate = $this->timezoneInterface->date()->format('Y-m-01');
            $endDate = $this->timezoneInterface->date()->format('Y-m-t');

            if($currentMonthEvenOrOdd != $orderFrequencyMonth) {
                $startDate = date('Y-m-01', strtotime('+1 month'));
                $endDate = date('Y-m-t', strtotime('+1 month'));
                $sameMonth = 1;
            }
            return $nextOrderDayBiMonthly = $this->orderNextDate->getBiMonthlyFrequencyDate($startDate, $endDate, $allowedMonthlyWeek, $orderFrequencyDay, $currentDate, $sameMonth);
        }
    }

    /**
     * Check Day Exist In First WeekDay
     *
     * @param string $orderFrequencyDay
     * @param string $currentDate
     * @return string
     */public function checkDayExistInFirstWeekDay($orderFrequencyDay, $currentDate)
    {
        $week = 0;
        $firstWeekDays =  (int)date("d", strtotime("first saturday of $currentDate"));
        $orderDay =  (int)date("d", strtotime("first ".$orderFrequencyDay." of $currentDate"));
        if($firstWeekDays < $orderDay) {
            $week = 1;
        }
        return $week;
    }
}
