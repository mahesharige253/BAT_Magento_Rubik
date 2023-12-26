<?php
namespace Bat\CustomerGraphQl\Model;

use Bat\Customer\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\CustomerGraphQl\Helper\Data as CustomerHelper;
use Bat\Integration\Helper\Data as Datas;
use Bat\Kakao\Model\Sms as KakaoSms;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\PasswordHistory\Helper\Config;
use Bat\PasswordHistory\Model\UsedPasswordFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class CustomerMobileAvailable
{
    public const XML_PATH_MOBILE_NUMBER_AVAILABLE_MESSAGE = "bat_customer/registration/mobile_number_available_message";

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Datas
     */
    protected $datas;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var UsedPasswordFactory
     */
    protected $usedPasswordFactory;

     /**
      * @var TimezoneInterface
      */
    private $timezoneInterface;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerHelper $customerHelper
     * @param Datas $datas
     * @param KakaoSms $kakaoSms
     * @param UsedPasswordFactory $usedPasswordFactory
     * @param TimezoneInterface $timezoneInterface
     * @param Config $config
     */
    public function __construct(
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        CustomerHelper $customerHelper,
        Datas $datas,
        KakaoSms $kakaoSms,
        UsedPasswordFactory $usedPasswordFactory,
        TimezoneInterface $timezoneInterface,
        Config $config
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->customerHelper = $customerHelper;
        $this->datas = $datas;
        $this->kakaoSms = $kakaoSms;
        $this->usedPasswordFactory = $usedPasswordFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->config = $config;
    }

    /**
     * Validate if mobile number is already registered or not.
     *
     * @param Array $args
     * @return array
     */
    public function isMobileAvailable($args)
    {
        $mobileNumber = $args['mobilenumber'];
        $customers = $this->helper->getCustomer("mobilenumber", $mobileNumber);
        $customerData = $customers->getData();
        $message = ($customers->getSize() > 0) ? $this->getMessage() : '';
        $outletId = '';

        if ($mobileNumber != '' && $customers->getSize() == 1 && $args['isOutletId'] == true) {
            $outletId = ($customers->getSize() == 1) ? $customerData[0]['outlet_id'] : '';
            /* Forget Outlet Id */
             $this->kakaoSms->sendSms($mobileNumber, ['outlet_id' => $outletId], 'ForgotID_001');
        }

        if ($mobileNumber != '' && $customers->getSize() == 1 && $args['isPasswordPin'] == true) {
            $outletId = ($customers->getSize() == 1) ? $customerData[0]['outlet_id'] : '';
            /* Forget Password Link Send */
            $customerRepo = $this->customerRepository->getById($customerData[0]['entity_id']);
            if ($customerRepo->getCustomAttribute('approval_status')) {
                $approvalStatus = $customerRepo->getCustomAttribute(
                    'approval_status'
                )->getValue();
                if (in_array($approvalStatus, [0, 2, 3, 5, 9])) {
                    throw new GraphQlInputException(__('Your customer account is not approved.'));
                }
            }
            if ($outletId !='') {
                $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
                $usedPasswordFactory = $this->usedPasswordFactory->create();
                $lastUpdatePasswordTime = $usedPasswordFactory->getLastItemDateTime($customerData[0]['entity_id']);
                if (!empty($lastUpdatePasswordTime)) {
                    $hours = $this->config->getAllowedHours();
                    $newDate = date('Y-m-d H:i:s', strtotime($lastUpdatePasswordTime[0]. ' + '.$hours.' hours'));
                    if ($newDate >= $currentDateTime) {
                        throw new GraphQlInputException(__(
                            'You can update new password after %1',
                            $newDate
                        ));
                    }
                }
            }

            $url = $this->customerHelper->getForgotPasswordPinUrl();
            $outletId = ($customers->getSize() == 1) ? $customerData[0]['outlet_id'] : '';
            $encryptData = $this->helper->saveEncryptUrl($outletId, "forgot_set_pinpassword");
            if ($encryptData != "") {
                $passwordResetLink = $url . '&id=' . $encryptData;
                if (!isset($customerData[0]['outlet_pin']) || $customerData[0]['outlet_pin'] == '') {
                    if ($customerRepo->getCustomAttribute('is_migrated')
                        && $customerRepo->getCustomAttribute('is_migrated')->getValue() == 1) {
                        $passwordResetLink .= "&ism=1";
                    }
                }

                $this->kakaoSms->sendSms(
                    $mobileNumber,
                    ['resetpasswordpin_link' => $passwordResetLink],
                    'ForgotPassword_001'
                );
            }
        }

        $outletIds = [];
        foreach ($customerData as $customerVal) {
            $outletIds[] = $customerVal['outlet_id'];
        }
        $outletId = implode(',', $outletIds);

        return [
            'is_mobile_available' => true,
            'message' => $message,
            'multistore' => ($customers->getSize() > 1) ? true : false,
            'is_mobile_exist' => ($customers->getSize() == 0) ? false : true,
            'outlet_id' => $outletId
        ];
    }

    /**
     * Get store config message for mobile number already associated to another outlet.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MOBILE_NUMBER_AVAILABLE_MESSAGE);
    }
}
