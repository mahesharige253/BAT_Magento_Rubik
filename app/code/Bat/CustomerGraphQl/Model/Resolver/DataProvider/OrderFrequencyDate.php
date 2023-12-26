<?php

namespace Bat\CustomerGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class OrderFrequencyDate
{
    /**
     * @var TimezoneInterface
     */
      private $timezoneInterface;

    /**
     * Construct method
     *
     * @param TimezoneInterface  $timezoneInterface
     */
    public function __construct(
        TimezoneInterface $timezoneInterface
    ) {
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Get Next Order Nearest Date as per Regular or Joker Order Frequency
     *
     * @param string $customer
     */
    public function getNextOrderDate($customer)
    {
        if ($customer->getCustomAttribute('bat_order_frequency')) {
            $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();
            $nextDate = $this->getNextOrderRegularFrequencyDate($customer);
            if ((!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
                    && !empty($customer->getCustomAttribute('joker_order_ecall_end_date')))
                    || (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
                    && !empty($customer->getCustomAttribute('joker_order_npi_end_date')))) {
                    return $this->getJokerOrderDate($customer, $orderFrequency, $this->getCurrentDate(), $nextDate);
            } else {
                return $nextDate;
            }
        }
    }

    /**
     * Get Next Order Date as per Regular Frequency
     *
     * @param string $customer
     */
    public function getNextOrderRegularFrequencyDate($customer)
    {
        if ($customer->getCustomAttribute('order_frequency_day')
        && $customer->getCustomAttribute('bat_order_frequency')) {
            $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();
            $day = $customer->getCustomAttribute('order_frequency_day')->getValue();
            $month = $customer->getCustomAttribute('order_frequency_month')->getValue();
            $week = $customer->getCustomAttribute('order_frequency_week')->getValue();
            if ($orderFrequency == 0) {
                $date = $this->getWeeklyDate($day, $customer);
            } elseif ($orderFrequency == 1) {
                $date = $this->getBiWeeklyDate($week, $day, $customer);
            } elseif ($orderFrequency == 2) {
                $date = $this->getMonthlyDate($week, $day, $customer);
            } elseif ($orderFrequency == 3) {
                $date = $this->getBiMonthlyDate($month, $day, $week, $customer);
            }
            return $date;
        }
    }

    /**
     * Get Started Date
     */
    public function getStartedDate()
    {
        return "2023-01-09";
    }

    /**
     * Is Time Allow Order
     *
     * @param  object $customer
     * @return boolean
     */
    public function isTimeAllowOrder($customer)
    {
        if (!empty($customer->getCustomAttribute('order_frequency_time_from'))
            && !empty($customer->getCustomAttribute('order_frequency_time_to'))
        ) {
            $currentTime = $this->timezoneInterface->date()->format('H:i:s');
            $orderFrequencyTimeFrom = $customer->getCustomAttribute('order_frequency_time_from')->getValue();
            $orderFrequencyTimeFrom = date("H:i:s", strtotime($orderFrequencyTimeFrom));
            $orderFrequencyTimeTo = $customer->getCustomAttribute('order_frequency_time_to')->getValue();
            $orderFrequencyTimeTo = date("H:i:s", strtotime($orderFrequencyTimeTo));
            if ($currentTime >= $orderFrequencyTimeFrom && $currentTime <= $orderFrequencyTimeTo) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Get Current Date
     */
    public function getCurrentDate()
    {
        // return "2023-12-11";
        return $this->timezoneInterface->date()->format('Y-m-d');
    }

    /**
     * Get Weekly Date
     *
     * @param string $day
     * @param object $customer
     */
    public function getWeeklyDate($day, $customer)
    {
        $currentDay = $this->timezoneInterface->date()->format('l');
        $isAllowTime = $this->isTimeAllowOrder($customer);
        if ($currentDay == $day && $isAllowTime == true) {
            return $this->getCurrentDate();
        } else {
            return date('Y-m-d', strtotime("next $day"));
        }
    }

    /**
     * Get Bi Monthly Date
     *
     * @param string $monthType
     * @param string $day
     * @param string $weekNum
     * @param object $customer
     */
    public function getBiMonthlyDate($monthType, $day, $weekNum, $customer)
    {
        $currentDate = $this->getCurrentDate();
        $currentM = $this->getCurrentMonth();
        $daynum = date("N", strtotime($day)) - 1;
        $isAllowTime = $this->isTimeAllowOrder($customer);
        $currentDay = $this->timezoneInterface->date()->format('l');
        if ($monthType == 'odd') {
            if ($this->getEvenOdd($currentM) != 'odd') {
                if ($weekNum == "week_one") {
                    $weekMonth = $this->firstWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                } elseif ($weekNum == "week_two") {
                    $weekMonth = $this->secondWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                } elseif ($weekNum == "week_three") {
                    $weekMonth = $this->thirdWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                } elseif ($weekNum == "week_four") {
                    $weekMonth = $this->fourWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                }
            } else {
                if ($weekNum == "week_one") {
                    return $this->getBiMonthlyFirstWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                } elseif ($weekNum == "week_two") {
                    return $this->getBiMonthlySecondWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                    ;
                } elseif ($weekNum == "week_three") {
                    return $this->getBiMonthlyThirdWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                    ;
                } elseif ($weekNum == "week_four") {
                    return $this->getBiMonthlyFourWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                }
            }
        } elseif ($monthType == 'even') {
            if ($this->getEvenOdd($currentM) != 'even') {
                if ($weekNum == "week_one") {
                    $weekMonth = $this->firstWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                } elseif ($weekNum == "week_two") {
                    $weekMonth = $this->secondWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                } elseif ($weekNum == "week_three") {
                    $weekMonth = $this->thirdWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                } elseif ($weekNum == "week_four") {
                    $weekMonth = $this->fourWeekOfMonthStartEndDate($currentM + 1);
                    return $this->getCalculateDate($weekMonth['start_date'], $daynum);
                }
            } else {
                if ($weekNum == "week_one") {
                    return $this->getBiMonthlyFirstWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                } elseif ($weekNum == "week_two") {
                    return $this->getBiMonthlySecondWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                } elseif ($weekNum == "week_three") {
                    return $this->getBiMonthlyThirdWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                } elseif ($weekNum == "week_four") {
                    return $this->getBiMonthlyFourWeekDate($currentM, $daynum, $isAllowTime, $currentDate);
                }
            }
        }
    }

    /**
     * GetBiMonthlyFourWeekDate
     *
     * @param int $currentM
     * @param int $daynum
     * @param boolean $isAllowTime
     * @param string $currentDate
     */
    public function getBiMonthlyFourWeekDate($currentM, $daynum, $isAllowTime, $currentDate)
    {
        $weekMonth = $this->fourWeekOfMonthStartEndDate($currentM);
        $calculateDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
        if ($currentDate > $calculateDate || $isAllowTime != true) {
            $weekMonth = $this->fourWeekOfMonthStartEndDate($currentM + 2);
        }
        return $this->getCalculateDate($weekMonth['start_date'], $daynum);
    }

    /**
     * GetBiMonthlyThirdWeekDate
     *
     * @param int $currentM
     * @param int $daynum
     * @param boolean $isAllowTime
     * @param string $currentDate
     */
    public function getBiMonthlyThirdWeekDate($currentM, $daynum, $isAllowTime, $currentDate)
    {
        $weekMonth = $this->thirdWeekOfMonthStartEndDate($currentM);
        $calculateDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
        if ($currentDate > $calculateDate || $isAllowTime != true) {
            $weekMonth = $this->thirdWeekOfMonthStartEndDate($currentM + 2);
        }
        return $this->getCalculateDate($weekMonth['start_date'], $daynum);
    }

    /**
     * GetBiMonthlySecondWeekDate
     *
     * @param int $currentM
     * @param int $daynum
     * @param boolean $isAllowTime
     * @param string $currentDate
     */
    public function getBiMonthlySecondWeekDate($currentM, $daynum, $isAllowTime, $currentDate)
    {
        $weekMonth = $this->secondWeekOfMonthStartEndDate($currentM);
        $calculateDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
        if ($currentDate > $calculateDate || $isAllowTime != true) {
            $weekMonth = $this->secondWeekOfMonthStartEndDate($currentM + 2);
        }
        return $this->getCalculateDate($weekMonth['start_date'], $daynum);
    }

    /**
     * GetBiMonthlyFirstWeekDate
     *
     * @param int $currentM
     * @param int $daynum
     * @param boolean $isAllowTime
     * @param string $currentDate
     */
    public function getBiMonthlyFirstWeekDate($currentM, $daynum, $isAllowTime, $currentDate)
    {
        $weekMonth = $this->firstWeekOfMonthStartEndDate($currentM);
        $calculateDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
        if ($currentDate > $calculateDate || $isAllowTime != true) {
            $weekMonth = $this->firstWeekOfMonthStartEndDate($currentM + 2);
        }
        return $this->getCalculateDate($weekMonth['start_date'], $daynum);
    }

    /**
     * Get Even Odd
     *
     * @param  int $number
     * @return string
     */
    public function getEvenOdd($number)
    {
        if ($number % 2 == 0) {
            $weekStatus = 'even';
        } else {
            $weekStatus = 'odd';
        }
        return $weekStatus;
    }

    /**
     * Get Bi Weekly Date
     *
     * @param string $week
     * @param string $day
     * @param object $customer
     */
    public function getBiWeeklyDate($week, $day, $customer)
    {
        $currentDate = $this->getCurrentDate();
        $currentM = $this->getCurrentMonth();
        $daynum = date("N", strtotime($day)) - 1;
        $isAllowTime = $this->isTimeAllowOrder($customer);
        if ($week == 'even') {
            $secondWeekMonth = $this->secondWeekOfMonthStartEndDate($currentM);
            $fourWeekMonth = $this->fourWeekOfMonthStartEndDate($currentM);
            $orderdateFirst = $this->getCalculateDate($secondWeekMonth['start_date'], $daynum);
            $orderdateSec = $this->getCalculateDate($fourWeekMonth['start_date'], $daynum);
            if ($secondWeekMonth['start_date'] >= $currentDate
            && $orderdateFirst >= $currentDate
            && $isAllowTime == true) {
                return $orderdateFirst;
            } elseif ($currentDate <= $secondWeekMonth['end_date']
            && $orderdateFirst >= $currentDate
            && $isAllowTime == true) {
                return $orderdateFirst;
            } elseif ($fourWeekMonth['start_date'] >= $currentDate
            && $orderdateSec >= $currentDate
            && $isAllowTime == true) {
                return $orderdateSec;
            } elseif ($currentDate <= $fourWeekMonth['end_date']
            && $orderdateSec >= $currentDate
            && $isAllowTime == true) {
                return $orderdateSec;
            } else {
                $finalDate = $this->getCalculateDate($secondWeekMonth['end_date'], $daynum + 8);
                if ($finalDate < $currentDate) {
                    $finalDate = $this->getCalculateDate($fourWeekMonth['end_date'], $daynum + 8);
                }
                return $finalDate;
            }
        } else {
            $firstWeekMonth = $this->firstWeekOfMonthStartEndDate($currentM);
            $thirdWeekMonth = $this->thirdWeekOfMonthStartEndDate($currentM);
            $orderdateFirst = $this->getCalculateDate($firstWeekMonth['start_date'], $daynum);
            $orderdateSec = $this->getCalculateDate($thirdWeekMonth['start_date'], $daynum);
            if ($firstWeekMonth['start_date'] >= $currentDate
            && $orderdateFirst >= $currentDate
            && $isAllowTime == true) {
                return $orderdateFirst;
            } elseif ($currentDate <= $firstWeekMonth['end_date']
            && $orderdateFirst >= $currentDate
            && $isAllowTime == true) {
                return $orderdateFirst;
            } elseif ($thirdWeekMonth['start_date'] >= $currentDate
            && $orderdateSec >= $currentDate
            && $isAllowTime == true) {
                return $orderdateSec;
            } elseif ($currentDate <= $thirdWeekMonth['end_date']
            && $orderdateSec >= $currentDate
            && $isAllowTime == true) {
                return $orderdateSec;
            } else {
                $finalDate = $this->getCalculateDate($firstWeekMonth['end_date'], $daynum + 8);
                if ($finalDate < $currentDate) {
                    $finalDate = $this->getCalculateDate($thirdWeekMonth['end_date'], $daynum + 8);
                }
                return $finalDate;
            }
        }
    }

    /**
     * Get Monthly Date
     *
     * @param string $noWeek
     * @param string $day
     * @param object $customer
     */
    public function getMonthlyDate($noWeek, $day, $customer)
    {
        $currentDate = $this->getCurrentDate();
        $currentM = $this->getCurrentMonth();
        $daynum = date("N", strtotime($day)) - 1;
        $isAllowTime = $this->isTimeAllowOrder($customer);
        if ($noWeek == "week_one") {
            $firstWeekMonth = $this->firstWeekOfMonthStartEndDate($currentM);
            $orderdate = $this->getCalculateDate($firstWeekMonth['start_date'], $daynum);
            if ($firstWeekMonth['start_date'] >= $currentDate
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } elseif ($currentDate <= $firstWeekMonth['end_date']
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } else {
                $weekMonth = $this->firstWeekOfMonthStartEndDate($currentM + 1);
                $finalDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                if ($finalDate < $currentDate) {
                    $weekMonth = $this->firstWeekOfMonthStartEndDate($currentM + 2);
                    $finaldate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                }
                return $finalDate;
            }
        } elseif ($noWeek == "week_two") {
            $secondWeekMonth = $this->secondWeekOfMonthStartEndDate($currentM);
            $orderdate = $this->getCalculateDate($secondWeekMonth['start_date'], $daynum);
            if ($secondWeekMonth['start_date'] >= $currentDate
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } elseif ($currentDate <= $secondWeekMonth['end_date']
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } else {
                $weekMonth = $this->secondWeekOfMonthStartEndDate($currentM + 1);
                $finalDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                if ($finalDate < $currentDate) {
                    $weekMonth = $this->secondWeekOfMonthStartEndDate($currentM + 2);
                    $finalDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                }
                return $finalDate;
            }
        } elseif ($noWeek == "week_three") {
            $thirdWeekMonth = $this->thirdWeekOfMonthStartEndDate($currentM);
            $orderdate = $this->getCalculateDate($thirdWeekMonth['start_date'], $daynum);
            if ($thirdWeekMonth['start_date'] >= $currentDate
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } elseif ($currentDate <= $thirdWeekMonth['end_date']
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } else {
                $weekMonth = $this->thirdWeekOfMonthStartEndDate($currentM + 1);
                $finalDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                if ($finalDate < $currentDate) {
                    $weekMonth = $this->thirdWeekOfMonthStartEndDate($currentM + 2);
                    $finalDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                }
                return $finalDate;
            }
        } elseif ($noWeek == "week_four") {
            $fourWeekMonth = $this->fourWeekOfMonthStartEndDate($currentM);
            $orderdate = $this->getCalculateDate($fourWeekMonth['start_date'], $daynum);
            if ($fourWeekMonth['start_date'] >= $currentDate
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } elseif ($currentDate <= $fourWeekMonth['end_date']
            && $orderdate >= $currentDate
            && $isAllowTime == true) {
                return $orderdate;
            } else {
                $weekMonth = $this->fourWeekOfMonthStartEndDate($currentM + 1);
                $finalDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                if ($finalDate < $currentDate) {
                    $fourWeekMonth = $this->fourWeekOfMonthStartEndDate($currentM + 2);
                    $finalDate = $this->getCalculateDate($weekMonth['start_date'], $daynum);
                }
                return $finalDate;
            }
        }
    }

    /**
     * Get Current Month
     */
    public function getCurrentMonth()
    {
        $currentDate = $this->getCurrentDate();
        $day = $this->dateDiffInDays($this->getStartedDate(), $currentDate);
        $result = $day / 7;

        if (is_float($result) || is_double($result)) {
            $result = ceil($result);
        }
        $currentMonth = $result / 4;
        if (is_float($currentMonth) || is_double($currentMonth)) {
            $currentMonth = ceil($currentMonth);
        }
        return $currentMonth;
    }

    /**
     * Get Calculate Date
     *
     * @param string $date
     * @param int $dayNum
     */
    public function getCalculateDate($date, $dayNum)
    {
        return date('Y-m-d', strtotime($date . " + $dayNum day"));
    }

    /**
     * Four Week Of Month Start End Date
     *
     * @param int $month
     */
    public function fourWeekOfMonthStartEndDate($month)
    {
        $fromDays = 28 * $month + 21;
        $toDays = $fromDays + 6;

        if ($month == 1) {
            $fromDays = 21;
            $toDays = $fromDays + 6;
        } elseif ($month == 2) {
            $fromDays = 28 + 21;
            $toDays = $fromDays + 6;
        } else {
            --$month;
            $fromDays = 28 * $month + 21;
            $toDays = $fromDays + 6;
        }
        // Set the start datedateDiffInDays
        $startDate = $this->getStartedDate();
        $startWeekDate = date('Y-m-d', strtotime($startDate . " + $fromDays day"));
        $endWeekDate = date('Y-m-d', strtotime($startDate . " + $toDays day"));
        return ['start_date' => $startWeekDate, 'end_date' => $endWeekDate];
    }

    /**
     * Third Week Of Month Start End Date
     *
     * @param int $month
     */
    public function thirdWeekOfMonthStartEndDate($month)
    {
        if ($month == 1) {
            $fromDays = 14;
            $toDays = $fromDays + 6;
        } elseif ($month == 2) {
            $fromDays = 28 + 14;
            $toDays = $fromDays + 6;
        } else {
            --$month;
            $fromDays = 28 * $month + 14;
            $toDays = $fromDays + 6;
        }
        // Set the start date
        $startDate = $this->getStartedDate();
        $startWeekDate = date('Y-m-d', strtotime($startDate . " + $fromDays day"));
        $endWeekDate = date('Y-m-d', strtotime($startDate . " + $toDays day"));
        return ['start_date' => $startWeekDate, 'end_date' => $endWeekDate];
    }

    /**
     * Second Week Of Month Start End Date
     *
     * @param int $month
     */
    public function secondWeekOfMonthStartEndDate($month)
    {
        if ($month == 1) {
            $fromDays = 7;
            $toDays = $fromDays + 6;
        } elseif ($month == 2) {
            $fromDays = 28 + 7;
            $toDays = $fromDays + 6;
        } else {
            --$month;
            $fromDays = 28 * $month + 7;
            $toDays = $fromDays + 6;
        }
        // Set the start date
        $startDate = $this->getStartedDate();
        $startWeekDate = date('Y-m-d', strtotime($startDate . " + $fromDays day"));
        $endWeekDate = date('Y-m-d', strtotime($startDate . " + $toDays day"));
        return ['start_date' => $startWeekDate, 'end_date' => $endWeekDate];
    }

    /**
     * First Week Of Month Start End Date
     *
     * @param int $month
     */
    public function firstWeekOfMonthStartEndDate($month)
    {
        if ($month == 1) {
            $fromDays = 0;
            $toDays = 6;
        } elseif ($month == 2) {
            $fromDays = 28;
            $toDays = $fromDays + 6;
        } else {
            --$month;
            $fromDays = 28 * $month;
            $toDays = $fromDays + 6;
        }
        // Set the start date
        $startDate = $this->getStartedDate();
        $startWeekDate = date('Y-m-d', strtotime($startDate . " + $fromDays day"));
        $endWeekDate = date('Y-m-d', strtotime($startDate . " + $toDays day"));
        return ['start_date' => $startWeekDate, 'end_date' => $endWeekDate];
    }

    /**
     * Date Diff In Days
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function dateDiffInDays($startDate, $endDate)
    {
        // Calculating the difference in timestamps
        $diff = strtotime($startDate) - strtotime($endDate);
        // 1 day = 24 hours
        // 24 * 60 * 60 = 86400 seconds
        return abs(round($diff / 86400));
    }

    /**
     * Get Joker Order Date
     *
     * @param  array $customer
     * @param  string $orderFrequency
     * @param  string $currentDate
     * @param  string|null $normalFrequencyNextDate
     */
    public function getJokerOrderDate($customer, $orderFrequency, $currentDate, $normalFrequencyNextDate = '')
    {
        $jokerOrderNextDateEcall = $jokerOrderNextDateNpi = '';
        if (!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_ecall_end_date'))
        ) {
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
            && !empty($customer->getCustomAttribute('joker_order_npi_end_date'))
        ) {
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
     * @param  string $currentDate
     * @param  string $frequencyNextDate
     * @param  array  $dateArray
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
}
