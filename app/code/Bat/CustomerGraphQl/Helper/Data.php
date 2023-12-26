<?php
namespace Bat\CustomerGraphQl\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\CustomerFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Server key config path
     */
    public const ORDER_FREQUENCY_WEEKLY = 'order_frequency/general/order_frequency_weekly';

    public const ORDER_FREQUENCY_BIWEEKLY = 'order_frequency/general/order_frequency_biweekly';

    public const ORDER_FREQUENCY_MONTHLY = 'order_frequency/general/order_frequency_monthly';
    public const ORDER_FREQUENCY_BIMONTHLY = 'order_frequency/general/order_frequency_bimonthly';

    public const ALLOW_SATURDAY_SUNDAY = 'order_frequency/general/allow_saturday_sunday';

    public const CUSTOMER_EMAIL = 'customer_credentials/general/customer_email';

    public const FORGOTPASSWORDPIN_URL = 'bat_customer/forgotpasswordpin_url/url';

    public const FRONTEND_BASE_URL = 'bat_general/frontend_base_url/base_url';

    public const REGISTRATION_RESUBMIT_URL = 'bat_customer/registration_resubmit_url/resubmit_url';

    public const MARKETINGCONSENT_TIME = 'marketingconsent_time/general/consent_time';

    public const ADDRESSCHANGE_URL = 'bat_customer/changeaddress_url/address_url';

    public const ADDRESSCHANGE_VALID_UPTO = 'bat_customer/changeaddress_url/address_url_time';

    public const SETPASSWORDPIN_VALID_UPTO = 'bat_customer/registration/setpinpassword_url_time';

    public const SETPASSWORDPIN_URL = 'bat_customer/registration/setpinpassword_url';

    public const BANNERSLIDER_TIMING = 'bannerslider/slider/autoplay_timing';

    public const FORGOTPINPASSWORD_EXPIRY = 'bat_customer/forgotpasswordpin_url/forgotpinpassword_url_time';

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * Data Construct
     *
     * @param Context $context
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->customerFactory = $customerFactory;
        parent::__construct($context);
    }

    /**
     * Get Order Frequency Weekly
     *
     * @return int
     */
    public function getFrequencyWeekly()
    {
        return $this->getConfig(self::ORDER_FREQUENCY_WEEKLY);
    }

    /**
     * Get Order Frequency BiWeekly
     *
     * @return int
     */
    public function getFrequencyBiWeekly()
    {
        return $this->getConfig(self::ORDER_FREQUENCY_BIWEEKLY);
    }

    /**
     * Get Order Frequency Monthly
     *
     * @return int
     */
    public function getFrequencyMonthly()
    {
        return $this->getConfig(self::ORDER_FREQUENCY_MONTHLY);
    }

    /**
     * Get Order Frequency Bi-Monthly
     *
     * @return int
     */
    public function getFrequencyBiMonthly()
    {
        return $this->getConfig(self::ORDER_FREQUENCY_BIMONTHLY);
    }

    public function getAllowSaturdaySunday()
    {
        return $this->getConfig(self::ALLOW_SATURDAY_SUNDAY);
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
     * Get Config path
     *
     * @param string $customerId
     * @return string|int
     */
    public function getCustomer($customerId)
    {
        $customerData = $this->customerFactory->create()->load($customerId);
        return $customerData->getData();
    }

    /**
     * Get Customer Email
     *
     * @return string
     */
    public function getCustomerEmail()
    {
        return $this->getConfig(self::CUSTOMER_EMAIL);
    }

    /**
     * Get Forgot Password/Pin URL
     *
     * @return string
     */
    public function getForgotPasswordPinUrl()
    {
        return $this->getConfig(self::FRONTEND_BASE_URL) . $this->getConfig(self::FORGOTPASSWORDPIN_URL);
    }

    /**
     * Get Set Password/Pin URL
     *
     * @return string
     */
    public function getSetPasswordPinUrl()
    {
        return $this->getConfig(self::FRONTEND_BASE_URL) . $this->getConfig(self::SETPASSWORDPIN_URL);
    }

    /**
     * Get Set Password/pin url valid upto
     *
     * @return int
     */
    public function getSetPasswordPinValidUpto()
    {
        return $this->getConfig(self::SETPASSWORDPIN_VALID_UPTO);
    }


    /**
     * Get Frontend Base URL
     *
     * @return string
     */
    public function getFrontendBaseUrl()
    {
        return $this->getConfig(self::FRONTEND_BASE_URL);
    }

    /**
     * Get Registration Re-submit URL
     *
     * @return string
     */
    public function getRegistrationReSubmitUrl()
    {
        return $this->getConfig(self::FRONTEND_BASE_URL) . $this->getConfig(self::REGISTRATION_RESUBMIT_URL);
    }

    /**
     * Marketing Consent time
     *
     * @return string
     */
    public function getMarketingConsentTime()
    {
        return $this->getConfig(self::MARKETINGCONSENT_TIME);
    }

    /**
     * Marketing Consent TimeIn
     *
     * @return int
     */
    public function getMarketingConsentTimeIn()
    {
        $configPath = 'marketingconsent_time/general/select';
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Address chnage url valid upto
     *
     * @return int
     */
    public function getAddressChangeUrlValidUpto()
    {
        return $this->getConfig(self::ADDRESSCHANGE_VALID_UPTO);
    }

    /**
     * Get Address chnage url
     *
     * @return string
     */
    public function getAddressChangeUrl()
    {
        return $this->getConfig(self::FRONTEND_BASE_URL) . $this->getConfig(self::ADDRESSCHANGE_URL);
    }

     /**
     * Get Banner Slider Timing
     *
     * @return string
     */
    public function getBannerSliderTiming()
    {
        return $this->getConfig(self::BANNERSLIDER_TIMING);
    }

    /**
     * Get Forgot Pin/Password Expiry time
     *
     * @return string
     */
    public function getForgotPinPasswordExpiry()
    {
        return $this->getConfig(self::FORGOTPINPASSWORD_EXPIRY);
    }
}