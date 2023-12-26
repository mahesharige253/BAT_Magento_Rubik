<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Bat\Customer\Helper\Data as CustomerData;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\LicenseUpload;
use Magento\CustomerGraphQl\Model\Customer\UpdateCustomerAccount;
use Magento\CustomerGraphQl\Model\Customer\Address\GetCustomerAddress;
use Magento\CustomerGraphQl\Model\Customer\Address\UpdateCustomerAddress as UpdateCustomerAddressModel;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Customer\Api\Data\CustomerInterface;
use Bat\Integration\Helper\Data;
use Bat\Customer\Model\SigunguCodeFactory;
use Bat\Kakao\Model\Sms as KakaoSms;

/**
 * Create customer account resolver
 */
class UpdateRejectedInformation implements ResolverInterface
{
    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var CustomerData
     */
    private $customerHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var LicenseUpload
     */
    private $licenseUpload;

    /**
     * @var UpdateCustomerAccount
     */
    private $updateCustomerAccount;

    /**
     * @var GetCustomerAddress
     */
    private $getCustomerAddress;

    /**
     * @var UpdateCustomerAddressModel
     */
    private $updateCustomerAddress;

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * @var CustomerInterface
     */
    private CustomerInterface $customer;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var SigunguCodeFactory
     */
    private SigunguCodeFactory $sigunguCodeFactory;

     /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     *
     * @param ExtractCustomerData $extractCustomerData
     * @param CustomerData $customerHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerFactory $customerFactory
     * @param LicenseUpload $licenseUpload
     * @param UpdateCustomerAccount $updateCustomerAccount
     * @param GetCustomerAddress $getCustomerAddress
     * @param UpdateCustomerAddressModel $updateCustomerAddress
     * @param EmailValidator $emailValidator
     * @param Data $data
     * @param KakaoSms $kakaoSms
     * @param SigunguCodeFactory $sigunguCodeFactory
     */
    public function __construct(
        ExtractCustomerData $extractCustomerData,
        CustomerData $customerHelper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerFactory $customerFactory,
        LicenseUpload $licenseUpload,
        UpdateCustomerAccount $updateCustomerAccount,
        GetCustomerAddress $getCustomerAddress,
        UpdateCustomerAddressModel $updateCustomerAddress,
        EmailValidator $emailValidator,
        Data $data,
        SigunguCodeFactory $sigunguCodeFactory,
        KakaoSms $kakaoSms,
    ) {
        $this->extractCustomerData = $extractCustomerData;
        $this->customerHelper = $customerHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->_customerFactory = $customerFactory;
        $this->licenseUpload = $licenseUpload;
        $this->updateCustomerAccount = $updateCustomerAccount;
        $this->getCustomerAddress = $getCustomerAddress;
        $this->updateCustomerAddress = $updateCustomerAddress;
        $this->emailValidator = $emailValidator;
        $this->data = $data;
        $this->sigunguCodeFactory = $sigunguCodeFactory;
        $this->kakaoSms = $kakaoSms;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $updateType = $args['input']['update_type'];
        if (!in_array($updateType, ['customer_rejected_field', 'address_change'])) {
            throw new GraphQlInputException(__('update_type value is required'));
        }
//        if (
//            isset($args['input']['mobilenumber'])
//            && !preg_match("/010 ([0-9]{3}|[0-9]{4}) [0-9]{4}$/", $args['input']['mobilenumber'])
//        ) {
//            throw new GraphQlInputException(__('Mobile number value is not valid'));
//        }

        if (isset($args['input']['secondary_email']) && !empty($args['input']['secondary_email'])) {
            if (!$this->emailValidator->isValid($args['input']['secondary_email'])) {
                throw new GraphQlInputException(__('Email is invalid'));
            }
        }

        $mobileNumber = $args['input']['mobilenumber'];
        $encryptedId = $args['input']['key_id'];
        $outletId = $this->data->decryptData($encryptedId);
        $decryptFields = explode(",", $outletId);
        $outletId = $decryptFields[0];
        if (trim($outletId) == '') {
            throw new GraphQlInputException(__('OutletId is required field'));
        }

        $customers = $this->customerHelper->getCustomer("outlet_id", $outletId);
        if ($customers->getSize() > 0) {
            $customer = $customers->getFirstItem();
            $customerId = $customer->getId();
            $customer = $this->customerRepositoryInterface->getById($customerId);
        } else {
            throw new GraphQlInputException(__('Outlet Id is not registered in Magento'));
        }
        if ($updateType == 'customer_rejected_field') {
            if (isset($args['input']['name']) && ($args['input']['name'] != '')) {
                $name = $args['input']['name'];
                $args['input']['firstname'] = $name;
            }

            if (isset($args['input']['consent_identifier'])) {
                $args['input']['consentform'] = $args['input']['consent_identifier'];
            }

            if (isset($args['input']['paper_forms'])) {
                $args['input']['bat_paper_forms'] = $args['input']['paper_forms'];
            }

            $passingConsent = '';
            $passingConsent = $args['input']['consent_identifier'];
            $passingConsentArr = explode(',', $passingConsent);
            $passingConsentFound = false;
            $existingConsentFound = false;
            $savedConsentFormArr = [];

            if($customer->getCustomAttribute('consentform')) {
                $savedConsentForm = $customer->getCustomAttribute('consentform')->getValue();
                $savedConsentFormArr = explode(',', $savedConsentForm);
            }

            if(in_array('announcements', $savedConsentFormArr)) {
                 $existingConsentFound = true;
            }
            if(in_array('announcements' , $passingConsentArr)) {
                 $passingConsentFound = true;
            }

            if($existingConsentFound == true && $passingConsentFound == false) { // Reject
                $args['input']['marketingconsent_updatedat'] = date('Y-m-d');
                $args['input']['marketingconsent'] = '';
                $args['input']['market_consent_given'] = 0;
                $mobileNumber = $args['input']['mobilenumber'];
                $currentTime = date('Y-m-d');
                $optOutDate = date('Y년m월d일', strtotime($currentTime));
                $params = ['mktconsentreject_date' => $optOutDate];
                $this->kakaoSms->sendSms($mobileNumber, $params, 'MarketingReject_001');
            }
            if($existingConsentFound == false && $passingConsentFound == true) { // Accept
                $args['input']['marketingconsent_updatedat'] = date('Y-m-d');
                $args['input']['market_consent_given'] = 1;
                $args['input']['marketingconsent'] = 'announcements';
                $mobileNumber = $args['input']['mobilenumber'];
                $currentTime = date('Y-m-d');
                $optInDate = date('Y년m월d일', strtotime($currentTime));
                $params = ['mktconsentaccept_date' => $optInDate];
                $this->kakaoSms->sendSms($mobileNumber, $params, 'MarketingAccept_001');
            }

            $args['input']['rejected_fields'] = [];
            if (!isset($args['input']['sigungu_code'])
            || $args['input']['sigungu_code'] ==''
            || $args['input']['sigungu_code'] == null) {
            throw new GraphQlInputException(__('Sigungu code value should be specified'));
            } else {
                $sigunguCode = $this->sigunguCodeFactory->create()->getCollection()
                                ->addFieldToFilter('sigungu_code', $args['input']['sigungu_code'])->load()->getFirstItem();
                if (!empty($sigunguCode->getData())) {
                    $args['input']['tax_code'] = $sigunguCode['tax_code'];
                    $args['input']['depot'] = $sigunguCode['depot'];
                    $args['input']['sales_office'] = $sigunguCode['depot'];
                    $args['input']['delivery_plant'] = $sigunguCode['depot'].'00';
                }
            }

            $customer->setCustomAttribute('approval_status', 3);
            $this->customerRepositoryInterface->save($customer);
        } elseif ($updateType == 'address_change') {
            $customer->setCustomAttribute('approval_status', 12);
            $this->customerRepositoryInterface->save($customer);
        }

        $customerRepository = $this->customerRepositoryInterface->getById($customerId);

        // Code to update the customer
        try {
            $customer = $this->updateCustomerAccount->execute(
                $customerRepository,
                $args['input'],
                $context->getExtensionAttributes()->getStore()
            );
        } catch (Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        $businessLicenseUpload = 0;
        $tobaccoLicenseUpload = 0;

        /* Check business license file exist or not. Upload business license file */
        if (isset($args['input']['business_license'])) {
            if (
                (isset($args['input']['business_license'][0]['business_name'])
                    && ($args['input']['business_license'][0]['business_name'] != ''))
                && (isset($args['input']['business_license'][0]['business_file'])
                    && ($args['input']['business_license'][0]['business_file'] != ''))
            ) {
                $businessLicenseName = $args['input']['business_license'][0]['business_name'];
                $businessLicenseImage = $args['input']['business_license'][0]['business_file'];
                $businessResponse = $this->licenseUpload->uploadBusinessLicense(
                    $businessLicenseName,
                    $businessLicenseImage,
                    $customerId
                );
                $businessLicenseUpload = 1;
            } else {
                throw new GraphQlInputException(__('Business License value missing'));
            }
        }

        /* Check tobacco seller license file exist or not. Upload tobacco seller license file */
        if (isset($args['input']['tobacco_seller_license'])) {
            if (
                (isset($args['input']['tobacco_seller_license'][0]['tobacco_name'])
                    && ($args['input']['tobacco_seller_license'][0]['tobacco_name'] != ''))
                && (isset($args['input']['tobacco_seller_license'][0]['tobacco_file'])
                    && ($args['input']['tobacco_seller_license'][0]['tobacco_file'] != ''))
            ) {
                $tobaccoLicenseName = $args['input']['tobacco_seller_license'][0]['tobacco_name'];
                $tobaccoLicenseImage = $args['input']['tobacco_seller_license'][0]['tobacco_file'];
                $tobaccoResponse = $this->licenseUpload->uploadTobaccoSellerLicense(
                    $tobaccoLicenseName,
                    $tobaccoLicenseImage,
                    $customerId
                );
                $tobaccoLicenseUpload = 1;
            } else {
                throw new GraphQlInputException(__('Tobacco Seller License value missing'));
            }
        }

        $data = $this->extractCustomerData->execute($customerRepository);
        if ($businessLicenseUpload == 1) {
            $filePath = '/business/' . $businessResponse['items'][0]['name'];
            $customerFactory = $this->_customerFactory->create()->load($customerId)->getDataModel();
            $customerFactory->setCustomAttribute('bat_business_license', $filePath);
            $this->customerRepositoryInterface->save($customerFactory);
        }
        if ($tobaccoLicenseUpload == 1) {
            $tobaccoFilePath = '/tobacco/' . $tobaccoResponse['items'][0]['name'];
            $customerFactory = $this->_customerFactory->create()->load($customerId)->getDataModel();
            $customerFactory->setCustomAttribute('bat_tobacco_seller_license', $tobaccoFilePath);
            $this->customerRepositoryInterface->save($customerFactory);
        }

        if ($updateType == 'address_change') {
            $this->customerHelper->deleteOldUrl($customerId);
            return ['customer' => $data];
        }

        // Code to update the address
        $shippingAddressId = $customerRepository->getDefaultShipping();
        $billingAddressId = $customerRepository->getDefaultBilling();

        $billingaddress = $this->getCustomerAddress->execute((int) $billingAddressId, (int) $customerId);
        $shippingaddress = $this->getCustomerAddress->execute((int) $shippingAddressId, (int) $customerId);

        if (!empty($args['input']['address'])) {
            $addressInput = $args['input']['address'];
        }

        $addressData = [];

        if (isset($addressInput['street'])) {
            $street = $addressInput['street'];
            if (isset($addressInput['city'])) {
                $street[] = $addressInput['city'];
            }
            $addressData['shipping_address']['street'] = $street;
            $addressData['billing_address']['street'] = $street;
        }

        if (isset($addressInput['postcode'])) {
            $addressData['shipping_address']['postcode'] = $addressInput['postcode'];
            $addressData['billing_address']['postcode'] = $addressInput['postcode'];
        }

        if (count($addressData) > 0) {
            $this->updateCustomerAddress->execute($shippingaddress, (array) $addressData['shipping_address']);
            $this->updateCustomerAddress->execute($billingaddress, (array) $addressData['billing_address']);
        }
        return ['customer' => $data];
    }
}
