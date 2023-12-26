<?php

namespace Bat\CustomerGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\Timezone\LocalizedDateToUtcConverterInterface;
use Magento\SalesGraphQl\Model\Order\OrderAddress;
use Bat\CustomerGraphQl\Helper\Data;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyDate;

class OrderFrequencyData
{

    /**
     * Joker order config path
     */
    public const JOKER_ORDER_ENABLED = 'bat_jokerorder/jokerorder/enabled';
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $_orderFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var LocalizedDateToUtcConverterInterface
     */
    private $utcConverter;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var OrderFrequencyDate
     */
    protected $orderFrequencyDate;

    /**
     * Construct method
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $session
     * @param TimezoneInterface $timezoneInterface
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderFactory
     * @param LocalizedDateToUtcConverterInterface $utcConverter
     * @param Data $helper
     * @param GetCustomer $getCustomer
     * @param OrderFrequencyDate $orderFrequencyDate
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $session,
        TimezoneInterface $timezoneInterface,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderFactory,
        LocalizedDateToUtcConverterInterface $utcConverter,
        Data $helper,
        GetCustomer $getCustomer,
        OrderFrequencyDate $orderFrequencyDate
    ) {
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->utcConverter = $utcConverter;
        $this->helper = $helper;
        $this->getCustomer = $getCustomer;
        $this->orderFrequencyDate = $orderFrequencyDate;
    }

    /**
     * Get From and To Date
     *
     * @param object $customer
     * @return array
     */
    public function getFromToDate($customer)
    {
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
        $orderFrequencyTimeFrom = $customer->getCustomAttribute('order_frequency_time_from')->getValue();
        $orderFrequencyTimeFrom = date("H:i:s", strtotime($orderFrequencyTimeFrom));
        $orderFrequencyTimeTo = $customer->getCustomAttribute('order_frequency_time_to')->getValue();
        $orderFrequencyTimeTo = date("H:i:s", strtotime($orderFrequencyTimeTo));
        $startDate = date("Y-m-d H:i:s", strtotime($currentDate . ' '.$orderFrequencyTimeFrom));
        $endDate = date("Y-m-d H:i:s", strtotime($currentDate . ' '.$orderFrequencyTimeTo));
        return ['from_date' => $startDate, 'to_date' => $endDate];
    }

    /**
     * Get Customer Order data
     *
     * @param object $customer
     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function getCustomerOrder($customer)
    {
        $fromToDate = $this->getFromToDate($customer);
        $orders = $this->_orderFactory->create(
        )->addFieldToFilter(
            'customer_id',
            $customer->getId()
        )->addFieldToFilter('created_at', ['gteq' => $fromToDate['from_date']])->addFieldToFilter(
            'created_at',
            ['lteq' => $fromToDate['to_date']]
        )->addFieldToFilter('order_type', ['nin' => $this->getEcallNpi()])->setOrder(
            'created_at',
            'desc'
        )->addFieldToFilter(
            'status', ['nin' => 'canceled']
        );
        return $orders;
    }

    /**
     * Get order Frequency function
     *
     * @param object $customer
     * @return array
     */
    public function getOrderFrequency($customer)
    {
        $jokerOrderFrequency = 0;
        $orderFrequencyDay = '';
        $cname = $customer->getFirstName() . " " . $customer->getLastName();
        $cid = $customer->getId();
        $err1 = 'Your can\'t create a new order because your order frequency has exceeded.';
        $err = 'For additional order contact Customer Care';
        $error = $err1 . $err;
        $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();

        $currentDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');

        if (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_npi_end_date'))) {
            $jokerOrderNpiStartDate = $customer->getCustomAttribute('joker_order_npi_start_date')->getValue();
            $jokerOrderNpiEndDate = $customer->getCustomAttribute('joker_order_npi_end_date')->getValue();
            $jokerOrderNpiEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderNpiEndDate));
            if (($currentDate >= $jokerOrderNpiStartDate) && ($currentDate <= $jokerOrderNpiEndDate)) {
                $jokerOrderFrequency ++;
            }
        }

        if (!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_ecall_end_date'))) {
            $jokerOrderEcallStartDate = $customer->getCustomAttribute('joker_order_ecall_start_date')->getValue();
            $jokerOrderEcallEndDate = $customer->getCustomAttribute('joker_order_ecall_end_date')->getValue();
            $jokerOrderEcallEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderEcallEndDate));
            if (($currentDate >= $jokerOrderEcallStartDate) && ($currentDate <= $jokerOrderEcallEndDate)) {
                $jokerOrderFrequency ++;
            }
        }

        if ($this->helper->getConfig(self::JOKER_ORDER_ENABLED) ==0) {
            $jokerOrderFrequency = 0;
        }

        if (!empty($customer->getCustomAttribute('order_frequency_day'))) {
            $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();
        }

        $allowDayTime = $this->isAvailableOrderFrequency($customer);
        $isAllowToNormalOrder = ($allowDayTime == 1) ? true : false;

        if ($orderFrequency == 0) {
            $orderPlaced = count($this->getCustomerOrder($customer));
            $totalOrder = $this->helper->getFrequencyWeekly();

            if ($orderPlaced >= $totalOrder && $jokerOrderFrequency ==0) {
                return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                ];
            } else {
                if ($jokerOrderFrequency ==0 && $allowDayTime == 2) {
                    return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                    ];
                } else {
                    $status = "You can place the order";
                    return [
                        'cust_id' => $cid,
                        'cust_name' => $cname,
                        'order_placed' => $orderPlaced,
                        'order_frequency' => $orderFrequency,
                        'total_order' => $totalOrder,
                        'joker_order_frequency' => $jokerOrderFrequency,
                        'message' => $status,
                        'order_frequency_day' => $orderFrequencyDay,
                        'success' => true,
                        'allow_normal_order' => $isAllowToNormalOrder
                    ];
                }
            }
        } elseif ($orderFrequency == 1) {
            $orderPlaced = count($this->getCustomerOrder($customer));
            $totalOrder = $this->helper->getFrequencyBiWeekly();

            if ($orderPlaced >= $totalOrder && $jokerOrderFrequency ==0) {
                return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                ];
            } else {
                if ($jokerOrderFrequency ==0 && $allowDayTime == 2) {
                    return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                    ];
                } else {
                    $status = "You can place the order";
                    return [
                        'cust_id' => $cid,
                        'cust_name' => $cname,
                        'order_placed' => $orderPlaced,
                        'order_frequency' => $orderFrequency,
                        'total_order' => $totalOrder,
                        'joker_order_frequency' => $jokerOrderFrequency,
                        'message' => $status,
                        'order_frequency_day' => $orderFrequencyDay,
                        'success' => true,
                        'allow_normal_order' => $isAllowToNormalOrder
                    ];
                }
            }
        } elseif ($orderFrequency == 2) {
            $orderPlaced = count($this->getCustomerOrder($customer));
            $totalOrder = $this->helper->getFrequencyMonthly();
            if ($orderPlaced >= $totalOrder && $jokerOrderFrequency ==0) {
                return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                ];
            } else {
                if ($jokerOrderFrequency ==0 && $allowDayTime == 2) {
                    return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                    ];
                } else {
                    $status = "You can place the order";
                    return [
                        'cust_id' => $cid,
                        'cust_name' => $cname,
                        'order_placed' => $orderPlaced,
                        'order_frequency' => $orderFrequency,
                        'total_order' => $totalOrder,
                        'joker_order_frequency' => $jokerOrderFrequency,
                        'order_frequency_day' => $orderFrequencyDay,
                        'message' => $status,
                        'success' => true,
                        'allow_normal_order' => $isAllowToNormalOrder
                    ];
                }
            }
        } elseif ($orderFrequency == 3) {
            $orderPlaced = count($this->getCustomerOrder($customer));
            $totalOrder = $this->helper->getFrequencyMonthly();
            if ($orderPlaced >= $totalOrder && $jokerOrderFrequency ==0) {
                return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                ];
            } else {
                if ($jokerOrderFrequency ==0 && $allowDayTime == 2) {
                    return [
                    'order_placed' => $orderPlaced,
                    'order_frequency' => $orderFrequency,
                    'total_order' => $totalOrder,
                    'message' => $error,
                    'allow_normal_order' => $isAllowToNormalOrder,
                    'order_frequency_day' => $orderFrequencyDay,
                    'success' => false
                    ];
                } else {
                    $status = "You can place the order";
                    return [
                        'cust_id' => $cid,
                        'cust_name' => $cname,
                        'order_placed' => $orderPlaced,
                        'order_frequency' => $orderFrequency,
                        'total_order' => $totalOrder,
                        'joker_order_frequency' => $jokerOrderFrequency,
                        'order_frequency_day' => $orderFrequencyDay,
                        'message' => $status,
                        'success' => true,
                        'allow_normal_order' => $isAllowToNormalOrder
                    ];
                }
            }
        }
    }

    /**
     * Allow Day Time
     *
     * @param object $customer
     * @return int
     */
    public function allowDayTime($customer)
    {
        $allowDayTime = 2;
        if ($this->isDayAllowOrder($customer) == 1) {
            if ($this->isTimeAllowOrder($customer)) {
                $allowDayTime = 1;
            }
        }
        return $allowDayTime;
    }

     /**
      * Week Of Month
      *
      * @param string $date
      * @return int
      */
    public function weekOfMonth($date)
    {
        $date = explode("-", $date);
        $dateNo = $date[2];
        $weekNo = $dateNo / 7;
        if (is_float($weekNo) == true) {
            $weekNo = ceil($weekNo);
        }
        return $weekNo;
    }

    /**
     * Get Even Odd
     *
     * @param int $number
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
     * Is Available Order Frequency
     *
     * @param object $customer
     * @return int
     */
    public function isAvailableOrderFrequency($customer)
    {
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
        $orderFrequencyDate = $this->orderFrequencyDate->getNextOrderRegularFrequencyDate($customer);
        $frequencyMonth = $customer->getCustomAttribute('order_frequency_month');
        $frequencyWeek = $customer->getCustomAttribute('order_frequency_week');
        $allowedCondition = 2;
        if (!empty($frequencyMonth) && !empty($frequencyWeek)) {
            $frequencyMonth = $frequencyMonth->getValue();
            $frequencyWeek = $frequencyWeek->getValue();
            $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();
            if ($orderFrequency == 0) {
                if ($orderFrequencyDate == $currentDate && $this->allowDayTime($customer) == 1) {
                    $allowedCondition = 1;
                }
            } elseif ($orderFrequency == 1) {
                if ($orderFrequencyDate == $currentDate &&
                 $this->allowDayTime($customer) == 1) {
                    $allowedCondition = 1;
                }
            } elseif ($orderFrequency == 2) {
                if ($orderFrequencyDate == $currentDate &&
                  $this->allowDayTime($customer) == 1) {
                    $allowedCondition = 1;
                }
            } elseif ($orderFrequency == 3) {
                if ($orderFrequencyDate == $currentDate &&
                  $this->allowDayTime($customer) == 1) {
                    $allowedCondition = 1;
                }
            }
        }
        return $allowedCondition;
    }

    /**
     * Get Monthly Week
     *
     * @param string $week
     * @return int
     */
    public function getMonthlyWeek($week)
    {
        if ($week == 'week_one') {
            return 1;
        } elseif ($week == 'week_two') {
            return 2;
        } elseif ($week == 'week_three') {
            return 3;
        } elseif ($week == 'week_four') {
            return 4;
        } elseif ($week == 'week_five') {
            return 5;
        }
    }

    /**
     * Is Day Allow Order
     *
     * @param object $customer
     * @return int
     */
    public function isDayAllowOrder($customer)
    {
        if (!empty($customer->getCustomAttribute('order_frequency_day'))) {
            $orderFrequencyDay = $customer->getCustomAttribute('order_frequency_day')->getValue();
            $currentDay = $this->timezoneInterface->date()->format('l');
            if ($currentDay == $orderFrequencyDay) {
                $allowedCondition = 1;
            } else {
                $allowedCondition = 2;
            }
        } else {
            $allowedCondition = 3;
        }
        return $allowedCondition;
    }

    /**
     * Is Time Allow Order
     *
     * @param object $customer
     * @return boolean
     */
    public function isTimeAllowOrder($customer)
    {
        if (!empty($customer->getCustomAttribute('order_frequency_time_from'))
            && !empty($customer->getCustomAttribute('order_frequency_time_to'))) {
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
     * Get Joker order type
     *
     * @return array
     */
    public function getEcallNpi()
    {
        return ['E-Call', 'NPI'];
    }

    /**
     * Get Normal order frequency
     *
     * @param object $customer
     * @return int
     */
    public function getOrderFrequencyStatus($customer)
    {
        $orderFrequency = $customer->getCustomAttribute('bat_order_frequency')->getValue();
        $allowOrder = $this->isAvailableOrderFrequency($customer);

        if ($allowOrder == 1) {
            $orderFrequencyStatus = 1;
            if ($orderFrequency == 0) {
                $orderPlaced = count($this->getCustomerOrder($customer));
                $totalOrder = $this->helper->getFrequencyWeekly();
                if ($orderPlaced >= $totalOrder) {
                    $orderFrequencyStatus = 3;
                }
            } elseif ($orderFrequency == 1) {
                $orderPlaced = count($this->getCustomerOrder($customer));
                $totalOrder = $this->helper->getFrequencyBiWeekly();
                if ($orderPlaced >= $totalOrder) {
                    $orderFrequencyStatus = 3;
                }
            } elseif ($orderFrequency == 2) {
                $orderPlaced = count($this->getCustomerOrder($customer));
                $totalOrder = $this->helper->getFrequencyMonthly();
                if ($orderPlaced >= $totalOrder) {
                    $orderFrequencyStatus = 3;
                }
            } elseif ($orderFrequency == 3) {
                $orderPlaced = count($this->getCustomerOrder($customer));
                $totalOrder = $this->helper->getFrequencyBiMonthly();
                if ($orderPlaced >= $totalOrder) {
                    $orderFrequencyStatus = 3;
                }
            }
        } else {
            $orderFrequencyStatus = 3;
        }
       
        return $orderFrequencyStatus;
    }
}
