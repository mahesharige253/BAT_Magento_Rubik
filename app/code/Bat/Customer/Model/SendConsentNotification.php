<?php

namespace Bat\Customer\Model;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\CustomerFactory;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\CustomerGraphQl\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;

class SendConsentNotification
{
    /**
     * Cron log enabled path
     */
    public const LOG_ENABLED_PATH = "marketingconsent_time/consent_notification/enabled";

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
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var KakaoSms
     */
    protected $kakaoSms;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * Constructor
     * @param CollectionFactory $customerCollectionFactory
     * @param TimezoneInterface $timezoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @param KakaoSms $kakaoSms
     * @param Data $data
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        CollectionFactory $customerCollectionFactory,
        TimezoneInterface $timezoneInterface,
        ScopeConfigInterface $scopeConfig,
        CustomerFactory $customerFactory,
        KakaoSms $kakaoSms,
        Data $data,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->scopeConfig = $scopeConfig;
        $this->customerFactory = $customerFactory;
        $this->kakaoSms = $kakaoSms;
        $this->data = $data;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * sendSmsAcceptedConsent
     */
    public function sendSmsAcceptedConsent()
    {
        $customers = $this->getCustomerAcceptedConsent();
        foreach ($customers as $customerObject) {
            $customer = $this->_customerRepositoryInterface->getById($customerObject->getId());
            $this->customerAcceptedConsent($customer);
        }
    }

    /**
     * CustomerAcceptedConsent
     */
    public function customerAcceptedConsent($customer)
    {
        $currentDay = $this->timezoneInterface->date()->format('Y-m-d');
        $time = $this->data->getMarketingConsentTime();
        $timePeriodIn = $this->data->getMarketingConsentTimeIn();

        if ($this->data->getMarketingConsentTimeIn() == 1) {
            $timePeriodIn = " months";
        } else { $timePeriodIn = " years"; }
             $timePeriod = ' +' . $time . $timePeriodIn;
        try {
            if($customer->getCustomAttribute('marketingconsent_updatedat') && $customer->getCustomAttribute('mobilenumber')) {
                $mobilenumber = $customer->getCustomAttribute('mobilenumber')->getValue();
               $customerConsentDate = $customer->getCustomAttribute('marketingconsent_updatedat')->getValue();
                 $new_date = date('Y-m-d', strtotime($customerConsentDate.$timePeriod));
                if ($new_date == $currentDay) {
                    /* Kakao SMS for Marketing communications consent - acceptance notification every 2 years */
                    if (!empty($mobilenumber)) {
                        $customerConsentDate = date("Y년 m월 d일", strtotime($customerConsentDate));
                        $params = ['mktconsentaccept_date' => $customerConsentDate];
                        $this->kakaoSms->sendSms($mobilenumber, $params, 'Marketing2Year_002');
                    }
                }
            }
        } catch (\Exception $e) {
            // Handle exceptions if needed
        }
    }

    /**
     * Get Accepted consent Customers
     *
     * @return object
     */
    public function getCustomerAcceptedConsent()
    {
        $collection = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('marketingconsent_updatedat', ['notnull' => true])
            ->addAttributeToSelect('mobilenumber')
            ->addAttributeToSelect('marketingconsent_updatedat');
        return $collection;
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
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/consent_acceptance_notification_cron.log');
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