<?php

namespace Bat\Sales\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\Kakao\Model\Sms as KakaoSms;

/**
 * @class DeliveryDelay
 */
class DeliveryDelay
{
    private const XML_PATH_CRON_STATUS = 'bat_sales/delivery_delay/cron_enable';
    private const XML_PATH_LOG_STATUS = 'bat_sales/delivery_delay/log_enable';
    private const XML_PATH_DELIVERY_DELAY_AFTER_DAYS = 'bat_sales/delivery_delay/days';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $orderCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var KakaoSms
     */
    private $kakaoSms;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $orderCollection
     * @param CustomerRepositoryInterface $customerRepository
     * @param TimezoneInterface $timezoneInterface
     * @param KakaoSms $kakaoSms
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $orderCollection,
        CustomerRepositoryInterface $customerRepository,
        TimezoneInterface $timezoneInterface,
        KakaoSms $kakaoSms
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollection;
        $this->customerRepository = $customerRepository;
        $this->timezoneInterface = $timezoneInterface;
        $this->kakaoSms = $kakaoSms;
    }

    /**
     * Process for delivery delay check
     *
     * @return void
     */
    public function checkDeliveryDelayOrders()
    {
        if ($this->getCronStatus()) {
            $this->addLog("Delivery Delay Cron Started.");
            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addFieldToSelect('*');
            $orderCollection->addAttributeToFilter('status', ['in' => ['processing']]);
            foreach ($orderCollection as $order) {
                $this->processOrder($order);
            }
            $this->addLog("Delivery Delay Cron Completed.");
        }
    }

    /**
     * Check order delivery time.
     *
     * @param object $order
     * @return void
     */
    public function processOrder($order)
    {
        $lastDeliveryTime = $this->getOrderDelayDateTime($order);
        $currentDateTime = date("Y-m-d");
        $mobileNumber = $this->getCustomerMobileNumber($order->getCustomerId());
        if (strtotime($currentDateTime) == strtotime($lastDeliveryTime)) {
            $this->addLog("Increment ID: " . $order->getIncrementId());
            $this->addLog("Current Date Time: " . $currentDateTime);
            $this->addLog("Order Created At: " . $order->getCreatedAt());
            $this->addLog("Delivery last Date: " . $lastDeliveryTime);
            if ($mobileNumber) {
                $this->kakaoSms->sendSms($mobileNumber, [], 'DeliveryDelay_001');
                $this->addLog("Kakao message has been sent.");
            }
            $this->addLog("-----------------------------------------");
        }
    }

    /**
     * Get possible last delivery time of order. After that it will be considered as a Delay.
     *
     * @param object $order
     * @return string
     */
    private function getOrderDelayDateTime($order)
    {
        $orderCreatedAtDate = $this->timezoneInterface->date($order->getShipDate())->format('Y-m-d H:i:s');
        $orderCreatedTime = $this->timezoneInterface->date($orderCreatedAtDate)->format('H:i:s');

        $orderCreatedDayNumber = date('w', strtotime($orderCreatedAtDate));
        $paymentDeadlineInDays = 4;
        if ($this->getDeliveryDelayDays() != '' && $this->getDeliveryDelayDays() > 0) {
            $paymentDeadlineInDays = $this->getDeliveryDelayDays() - 1;
        }

        $beforeElevenPm = strtotime('23:00:00');
        $weekendDays = 0;
        if ($orderCreatedDayNumber == 6) {// 0 means Sunday and 6 means Saturday
            $weekendDays += 2;
        }
        if ($orderCreatedDayNumber == 0) {// 0 means Sunday and 6 means Saturday
            $weekendDays += 1;
        }

        $noOfDays = $weekendDays + $paymentDeadlineInDays;
        if ($beforeElevenPm < strtotime($orderCreatedTime)) {
            $noOfDays++;
        }

        $addedDay = " +".$noOfDays. " days";
        return date('Y-m-d', strtotime($orderCreatedAtDate. $addedDay));
    }

    /**
     * Get Cron Enable Status
     *
     * @return bool
     */
    private function getCronStatus()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_CRON_STATUS);
    }

    /**
     * Get Cron Enable Status
     *
     * @return bool
     */
    private function getDeliveryDelayDays()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DELIVERY_DELAY_AFTER_DAYS);
    }

    /**
     * Get customer mobile number
     *
     * @param int $customerId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerMobileNumber($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerMobileAttribute = $customer->getCustomAttribute('mobilenumber');
            return ($customerMobileAttribute) ? $customerMobileAttribute->getValue() : '';
        } catch (\Magento\Framework\Exception\NoSuchEntityException) {
            return '';
        } catch (\Magento\Framework\Exception\LocalizedException) {
            return '';
        }
    }

    /**
     * Delivery delay log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    private function addLog($message)
    {
        if ($this->scopeConfig->getValue(self::XML_PATH_LOG_STATUS)) {
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/delivery_delay.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            if (is_array($message)) {
                $logger->info(print_r($message, true));
            } else {
                $logger->info($message);
            }
        }
    }
}
