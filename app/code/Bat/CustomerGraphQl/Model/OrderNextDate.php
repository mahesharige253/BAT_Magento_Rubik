<?php
namespace Bat\CustomerGraphQl\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\CustomerGraphQl\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyData;

class OrderNextDate
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
     * Construct method
     *
     * @param TimezoneInterface $timezoneInterface
     * @param Data $helper
     * @param Session $session
     * @param CollectionFactory $orderFactory
     * @param OrderFrequencyData $orderFrequencyData
     */
    public function __construct(
        TimezoneInterface $timezoneInterface,
        Data $helper,
        Session $session,
        CollectionFactory $orderFactory,
        OrderFrequencyData $orderFrequencyData
    ) {
        $this->timezoneInterface = $timezoneInterface;
        $this->helper = $helper;
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->orderFrequencyData = $orderFrequencyData;
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
        $next = '';

        if ($orderFrequency == 0) {
            $orderPlaced = count($this->getWeekly());
            $totalOrder = $this->helper->getFrequencyWeekly();
            if (($this->orderFrequencyData->allowDayTime($customer) == 1) && ($totalOrder - $orderPlaced > 0)){
                $nextDate = $currentDate;
            }
            elseif ($this->isJokerOrder($customer, $currentDate) == true) {
                if(($this->orderFrequencyData->allowDayTime($customer) == 1)  && ($isTimeAllowToOrder == true)) {
                    $normalFrequencyNextDate = date('Y-m-d', strtotime("next ". $orderFrequencyDay));
                } else {
                    $normalFrequencyNextDate = date('Y-m-d', strtotime("second ". $orderFrequencyDay));
                }
                $nextDate = $this->getJokerOrderDate($customer, $orderFrequency, $currentDate, $normalFrequencyNextDate);
            } else {
                $nextDate = date('Y-m-d', strtotime("next ". $orderFrequencyDay));
                if($currentDate == $nextDate) {
                    $nextDate = date('Y-m-d', strtotime("second ". $orderFrequencyDay));
                }
            }

            if ((!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_ecall_end_date')))
                || (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_npi_end_date')))) {
                return $this->getJokerOrderDate($customer, $orderFrequency, $currentDate, $nextDate);
            } else {
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
            if ((!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_ecall_end_date')))
                || (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_npi_end_date')))) {

                return $this->getJokerOrderDate($customer, $orderFrequency, $currentDate, $next);
            }else{
                return $next;
            }
        }

        if ($orderFrequency == 2) {
            $orderFrequencyMonth = $customer->getCustomAttribute('order_frequency_month')->getValue();
            $orderFrequencyWeek = $customer->getCustomAttribute('order_frequency_week')->getValue();

            $allowedMonthlyWeek = $this->orderFrequencyData->getMonthlyWeek($orderFrequencyWeek);
            $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();

            $startDate = $this->timezoneInterface->date()->format('Y-m-01');
            $endDate = $this->timezoneInterface->date()->format('Y-m-t');

            $nextOrderDayBiMonthly = $this->getFrequencyDate($startDate, $endDate, $allowedMonthlyWeek, $orderFrequencyDay, $currentDate);

            if ((!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_ecall_end_date')))
                || (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_npi_end_date')))) {
                    return $this->getJokerOrderDate($customer, $orderFrequency, $currentDate, $nextOrderDayBiMonthly);
             }else{
                return $nextOrderDayBiMonthly;
            }
        }

        if ($orderFrequency == 3) {
            $sameMonth = 0;
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

            $nextOrderDayBiMonthly = $this->getBiMonthlyFrequencyDate($startDate, $endDate, $allowedMonthlyWeek, $orderFrequencyDay, $currentDate, $sameMonth);

            if ((!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_ecall_end_date')))
                || (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_npi_end_date')))) {
                    return $this->getJokerOrderDate($customer, $orderFrequency, $currentDate, $nextOrderDayBiMonthly);
             }else{
                return $nextOrderDayBiMonthly;
            }
        }
    }

    /**
     * Get Joker Order Date
     *
     * @param array $customer
     * @param string $orderFrequency
     * @param string $currentDate
     * @return string
     */
    public function getJokerOrderDate($customer, $orderFrequency, $currentDate, $normalFrequencyNextDate = '')
    {
        $jokerOrderNextDateEcall = $jokerOrderNextDateNpi = '';
        if (!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_ecall_end_date'))) {
            $jokerOrderEcallStartDate = $customer->getCustomAttribute('joker_order_ecall_start_date')->getValue();
           $jokerOrderEcallEndDate = $customer->getCustomAttribute('joker_order_ecall_end_date')->getValue();
            $jokerOrderEcallEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderEcallEndDate));
            $currentDate;

            if (($currentDate >= $jokerOrderEcallStartDate) && ($currentDate <= $jokerOrderEcallEndDate)) {
                $jokerOrderNextDateEcall = $currentDate;
            } elseif (($currentDate <= $jokerOrderEcallStartDate)) {
                $jokerOrderNextDateEcall = $jokerOrderEcallStartDate;
            }
        }
        if (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_npi_end_date'))) {
            $jokerOrderNpiStartDate = $customer->getCustomAttribute('joker_order_npi_start_date')->getValue();
            $jokerOrderNpiEndDate = $customer->getCustomAttribute('joker_order_npi_end_date')->getValue();
            $jokerOrderNpiEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderNpiEndDate));
            if (($currentDate >= $jokerOrderNpiStartDate) && ($currentDate <= $jokerOrderNpiEndDate)) {
                $jokerOrderNextDateNpi = $currentDate;
            } elseif (($currentDate <= $jokerOrderNpiStartDate)) {
                $jokerOrderNextDateNpi = $jokerOrderNpiStartDate;
            }
        }
        $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();
        if ($orderFrequency == 0) {
            $frequencyNextDate = $normalFrequencyNextDate;
            $dateArray = [$jokerOrderNextDateEcall, $jokerOrderNextDateNpi, $frequencyNextDate];

            return $this->getNearestDate($currentDate, $frequencyNextDate, $dateArray);
        } elseif ($orderFrequency == 1) {
            $frequencyNextDate =  $normalFrequencyNextDate;
            $dateArray = [$jokerOrderNextDateEcall, $jokerOrderNextDateNpi, $frequencyNextDate];

            return  $this->getNearestDate($currentDate, $frequencyNextDate, $dateArray);
        } elseif ($orderFrequency == 2) {
            $frequencyNextDate =  $normalFrequencyNextDate;
            $dateArray = [$jokerOrderNextDateEcall, $jokerOrderNextDateNpi, $frequencyNextDate];

            return  $this->getNearestDate($currentDate, $frequencyNextDate, $dateArray);
        } elseif ($orderFrequency == 3) {
            $frequencyNextDate =  $normalFrequencyNextDate;
            $dateArray = [$jokerOrderNextDateEcall, $jokerOrderNextDateNpi, $frequencyNextDate];

            return  $this->getNearestDate($currentDate, $frequencyNextDate, $dateArray);
        }
    }

    /**
     * Get Nearest date
     *
     * @param string $currentDate
     * @param string $frequencyNextDate
     * @param array $dateArray
     * @return string
     */
    public function getNearestDate($currentDate, $frequencyNextDate, $dateArray)
    {
        $interval = [];
        $closest = '';
        foreach ($dateArray as $day) {
            $interval[] = abs(strtotime($currentDate) - strtotime($day));
        }

        if (!empty($interval)) {
            asort($interval);
            $closest = key($interval);
            return date("Y-m-d", strtotime($dateArray[$closest]));
        } else {
            return date("Y-m-d", strtotime($frequencyNextDate));
        }
    }

     /**
      * Get Weekly Order data

      * @return array
      */
    public function getWeekly()
    {
        $customerId = $this->_session->getCustomer()->getId();
        $firstday = date('Y-m-d', strtotime("previous friday"));
        $lastDay = date("Y-m-d", strtotime("this friday"));
        $startDate = date("Y-m-d H:i:s", strtotime($firstday . ' 17:00:00'));
        $endDate = date("Y-m-d H:i:s", strtotime($lastDay . ' 17:00:00'));

        $finalstart = $this->timezoneInterface->convertConfigTimeToUtc($startDate);
        $finalend = $this->timezoneInterface->convertConfigTimeToUtc($endDate);

        $orders = $this->_orderFactory->create(
        )->addFieldToFilter(
            'customer_id',
            $customerId
        )->addFieldToFilter('created_at', ['gteq' => $finalstart])->addFieldToFilter(
            'created_at',
            ['lteq' => $finalend]
        )->addFieldToFilter('order_type', ['nin' => $this->getEcallNpi()])->setOrder(
            'created_at',
            'desc'
        );
        return $orders;
    }

    /**
     * Get Joker order type
     *
     * @return array
     */
    public function getEcallNpi()
    {
        return ['E-Call', 'NPI'];
    }

    /**
     * Get Week in words by number
     * @param string int
     *
     * @return string
     */
    public function getWeekInWords($weekNumber)
    {
        if($weekNumber == 1) {
            return 'First';
        } else if($weekNumber == 2) {
            return 'Second';
        }else if($weekNumber == 3) {
            return 'Third';
        } else if($weekNumber == 4) {
            return 'Fourth';
        } else if($weekNumber == 5) {
            return 'Fifth';
        }
    }

    /**
     * Is joker order date range active
     * @param string string
     *
     * @return boolean
     */
    public function isJokerOrder($customer, $currentDate)
    {
        $isJokerOrder = false;
        if ((!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_ecall_end_date')))
            || (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_npi_end_date')))) {

            /*To check Ecall and NPI date range exist in current date*/
           if($customer->getCustomAttribute('joker_order_ecall_start_date') && $customer->getCustomAttribute('joker_order_ecall_end_date')) {
                $jokerOrderEcallStartDate = $customer->getCustomAttribute('joker_order_ecall_start_date')->getValue();
                $jokerOrderEcallEndDate = $customer->getCustomAttribute('joker_order_ecall_end_date')->getValue();
               $jokerOrderEcallEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderEcallEndDate));
               if (($currentDate >= $jokerOrderEcallStartDate) && ($currentDate <= $jokerOrderEcallEndDate)) {
                    $isJokerOrder = true;
               }
            }

            if($customer->getCustomAttribute('joker_order_npi_start_date') && $customer->getCustomAttribute('joker_order_npi_end_date')) {
                $jokerOrderNPIStartDate = $customer->getCustomAttribute('joker_order_npi_start_date')->getValue();
                $jokerOrderNPIEndDate = $customer->getCustomAttribute('joker_order_npi_end_date')->getValue();
                $jokerOrderNPIEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderNPIEndDate));
                if (($currentDate >= $jokerOrderNPIStartDate) && ($currentDate <= $jokerOrderNPIEndDate)) {
                    $isJokerOrder = true;
               }
            }
        }
        return $isJokerOrder;
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

    /**
     * Monthly: Check Day Exist In First WeekDay
     *
     * @param string $orderFrequencyDay
     * @param string $currentDate
     * @return string
     */
    public function checkMonthlyDayExistInFirstWeekDay($orderFrequencyDay, $currentDate, $weekInText)
    {
        $next = $currentDate;
        for ($i = 1; $i <= 12; $i++) {
            $firstWeekDays =  (int)date("d", strtotime("first saturday of $currentDate"));
            $orderDay =  (int)date("d", strtotime("first ".$orderFrequencyDay." of $currentDate"));
            if($firstWeekDays < $orderDay) {
                $next = date('Y-m-d',strtotime($weekInText.' '.$orderFrequencyDay.' of  +'.$i.' month'));
                $firstWeekDaysNext =  (int)date("d", strtotime("first saturday of $next"));
                $orderDayNext =  (int)date("d", strtotime("first ".$orderFrequencyDay." of $next"));
                if($firstWeekDaysNext >= $orderDayNext) {
                    break;
                }
            }
        }
        return $next;
    }

    public function getFrequencyDate($startDate, $endDate, $week, $day, $currentDate)
    {
        $nextDay = $this->getNextDate($startDate, $endDate, $week, $day, $currentDate);
        for($i = 0; $i <= 12; $i++) {
            if ($nextDay == '' || $currentDate > $nextDay) {
                $startDate = date('Y-m-01', strtotime('+'.$i.' month'));
                $endDate = date('Y-m-t', strtotime('+'.$i.' month'));
                $nextDay = $this->getNextDate($startDate, $endDate, $week, $day, $currentDate);
            }else{
                break;
            }
        }

        return $nextDay;
    }

    public function getBiMonthlyFrequencyDate($startDate, $endDate, $week, $day, $currentDate, $sameMonth)
    {
        $nextDay = $this->getNextDate($startDate, $endDate, $week, $day, $currentDate);
        if($nextDay == '') {
            $j = 0;
            if($sameMonth == 1) {
                $j = 1;
            }
            for($i = $j; $i <= 24; $i+=2) {
                if ($nextDay == '' || $currentDate > $nextDay) {
                    $startDate = date('Y-m-01', strtotime('+'.$i.' month'));
                    $endDate = date('Y-m-t', strtotime('+'.$i.' month'));
                    $nextDay = $this->getNextDate($startDate, $endDate, $week, $day, $currentDate);
                }else{
                    break;
                }
            }
        }
        return $nextDay;
    }

    public function getNextDate($startDate, $endDate, $week, $day, $currentDate)
    {
        $firstWeekDays =  (int)date("d", strtotime("first saturday of $startDate"));
        $allowedMonthlyWeek = $this->orderFrequencyData->getMonthlyWeek($week);
        $weekInText = $this->getMonthlyWeekInText($week);
        if($firstWeekDays == 3 && ($week == 5) && ($day == 'Friday')) {
                $weekInText = 'fifth';
        }
        if($firstWeekDays == 4 && $week == 5 && ($day == 'Thursday' || $day == 'Friday')) {
                $weekInText = 'fifth';
        }
        if($firstWeekDays == 5 && $week == 5 && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $weekInText = 'fifth';
        }
        if($firstWeekDays == 6 && ($week == 5) && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $weekInText = 'fifth';
        }

        if($firstWeekDays == 3 && ($week == 4) && ($day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'fourth';
        }
        if($firstWeekDays == 4 && ($week == 4) && ($day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'fourth';
        }
        if($firstWeekDays == 5 && ($week == 4) && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'fourth';
        }
        if($firstWeekDays == 6 && ($week == 4) && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'fourth';
        }

        if($firstWeekDays == 3 && ($week == 3) && ($day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'third';
        }
        if($firstWeekDays == 4 && ($week == 3) && ($day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'third';
        }
        if($firstWeekDays == 5 && ($week == 3) && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'third';
        }
        if($firstWeekDays == 6 && ($week == 3) && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'third';
        }

        if($firstWeekDays == 3 && ($week == 2) && ($day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'second';
        }
        if($firstWeekDays == 4 && ($week == 2) && ($day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'second';
        }
        if($firstWeekDays == 5 && ($week == 2) && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'second';
        }
        if($firstWeekDays == 6 && ($week == 2) && ($day == 'Wednesday' || $day == 'Thursday' || $day == 'Friday')) {
                $week = $week + 1;
                $weekInText = 'second';
        }

        if($day == 'Saturday') {
            $weekInText = $this->getWeekInWords($week);
        }

        if($week == 1) {
            $orderDay =  (int)date("d", strtotime("first ".$day." of $startDate"));
            $weekInText = '';
            if($firstWeekDays < $orderDay) {
                return $nextDay = '';
            }
        }
        $startDateMonth =  date("F", strtotime($startDate));
        $startDateYear =  date("Y", strtotime($startDate));
        $nextDay = date('Y-m-d', strtotime($weekInText.' '.$day.' '.$startDateMonth.' '.$startDateYear));
        if($endDate < $nextDay || $nextDay < $currentDate) {
            return $nextDay = '';
        }

        return $nextDay;
    }

    /**
     * Get Monthly Week
     *
     * @param string $week
     * @return int
     */
    public function getMonthlyWeekInText($week)
    {
        if ($week == 1) {
            return '';
        } elseif ($week == 2) {
            return 'First';
        } elseif ($week == 3) {
            return 'Second';
        } elseif ($week == 4) {
            return 'Third';
        }  elseif ($week == 5) {
            return 'Fourth';
        }
    }
}