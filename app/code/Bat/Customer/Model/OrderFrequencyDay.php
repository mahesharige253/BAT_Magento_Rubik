<?php
namespace Bat\Customer\Model;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\Kakao\Model\Sms as KakaoSms;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyDate;

class OrderFrequencyDay
{
    /**
     * Cron log enabled path
     */
    public const LOG_ENABLED_PATH = "order_frequency/order_day/enabled";

    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @var OrderFrequencyDate
     */
    protected $orderFrequencyDate;

    /**
     * Constructor
     * @param CollectionFactory $customerCollectionFactory
     * @param TimezoneInterface $timezoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param KakaoSms $kakaoSms
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param OrderFrequencyDate $orderFrequencyDate
     */
    public function __construct(
        CollectionFactory $customerCollectionFactory,
        TimezoneInterface $timezoneInterface,
        ScopeConfigInterface $scopeConfig,
        KakaoSms $kakaoSms,
        CustomerRepositoryInterface $customerRepositoryInterface,
        OrderFrequencyDate $orderFrequencyDate
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->scopeConfig = $scopeConfig;
        $this->kakaoSms = $kakaoSms;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->orderFrequencyDate = $orderFrequencyDate;
    }

    /**
     * SendSmsOrderDay
     */
    public function sendSmsOrderDay()
    {
        $currentDay = $this->timezoneInterface->date()->format('l');
        $customers = $this->getOrderDayCustomers($currentDay);
        foreach ($customers as $customerObject) {
            $customer = $this->_customerRepositoryInterface->getById($customerObject->getId());
            if ($this->isAvailableOrderFrequency($customer) == 1) {
                $this->addLog('Customer id: '.$customer->getId());
                /* Kakao Message to customer for notifying order frequency day */
                if ($customer->getCustomAttribute('mobilenumber')) {
                    $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                    $this->kakaoSms->sendSms($mobileNumber, [], 'OrderDay_001');
                }
            }
        }
    }

    /**
     * Get Order Day Customers
     *
     * @param string $day
     * @return object
     */
    public function getOrderDayCustomers($day)
    {
        $collection = $this->customerCollectionFactory->create()
                   ->addAttributeToFilter('order_frequency_day', $day)
                   ->addAttributeToFilter('order_frequency_time_from', ['notnull' => true])
                   ->addAttributeToFilter('order_frequency_time_to', ['notnull' => true])
                   ->addAttributeToFilter('approval_status', 1)
                   ->addAttributeToFilter('account_closing_date', ['null' => true]);
        return $collection;
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
        $allowedCondition = 2;
        if ($this->orderFrequencyDate->getNextOrderRegularFrequencyDate($customer) == $currentDate) {
                $allowedCondition = 1;
        }
        return $allowedCondition;
    }

    /**
     * Add Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addLog($message)
    {
        $config = $this->getConfig(self::LOG_ENABLED_PATH);
        if ($config) {
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/order_frequency_day_cron.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($message);
        }
    }

    /**
     * Get Config
     *
     * @param string $config_path
     * @return boolean
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
