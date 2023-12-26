<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerFactory as CustomerResourceFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Bat\CustomerGraphQl\Helper\Data;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\Constraint\IsEmpty;
use Bat\Kakao\Model\Sms as KakaoSms;

class AccountInfoConsent implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var Customer
     */
    private $customerFactory;

    /**
     * @var CustomerResourceFactory
     */
    private $customerResourceFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;


    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param Customer $customerFactory
     * @param CustomerResourceFactory $customerResourceFactory
     * @param AttributeRepositoryInterface $attributeRepository
     * @param Data $data
     * @param DateTime $dateTime
     * @param KakaoSms $kakaoSms
     */
    public function __construct(
        GetCustomer $getCustomer,
        Customer $customerFactory,
        CustomerResourceFactory $customerResourceFactory,
        AttributeRepositoryInterface $attributeRepository,
        Data $data,
        DateTime $dateTime,
        KakaoSms $kakaoSms
    ) {
        $this->getCustomer = $getCustomer;
        $this->customerFactory = $customerFactory;
        $this->customerResourceFactory = $customerResourceFactory;
        $this->attributeRepository = $attributeRepository;
        $this->data = $data;
        $this->dateTime = $dateTime;
        $this->kakaoSms = $kakaoSms;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $parameterConsent = [];
        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $registeredConsent = [];
        $inputConsent = '';
        $parameter = $args['input']['consent_identifier'];
        $parameterConsent = explode(',', $parameter);
        if (($key = array_search('collection_info', $parameterConsent)) !== false) {
            unset($parameterConsent[$key]);
        }
        if (($key = array_search('account_marketing', $parameterConsent)) !== false) {
            unset($parameterConsent[$key]);
        }
        $inputConsent = implode(',', $parameterConsent);
        if (!isset($args['input']['consent_identifier'])) {
            throw new GraphQlInputException(__('Consent Identifier value should be specified'));
        }
            $customer = $this->customerFactory->load($customerId);
            $customerData = $customer->getDataModel();
            $currentDate = date('Y-m-d');
            $newArr = [];
            $regArr = [];
            if(!$customerData->getCustomAttribute('account_consent_given')){
                if (!empty($customerData->getCustomAttribute('consentform'))) {
                    $selectedConsent = $customerData->getCustomAttribute('consentform')->getValue();
                    $registeredConsent = explode(',', $selectedConsent);
                    $attributeCode = 'consentform';
                    $consent3 = array_search('announcements', $registeredConsent);
                    if ($consent3 != '') {
                        $newArr[] = 'announcements';
                    }
                }
            }
            elseif($customerData->getCustomAttribute('account_consent_given')->getValue() == 0){
                if (!empty($customerData->getCustomAttribute('consentform'))) {
                    $selectedConsent = $customerData->getCustomAttribute('consentform')->getValue();
                    $registeredConsent = explode(',', $selectedConsent);
                    $attributeCode = 'consentform';
                    $consent3 = array_search('announcements', $registeredConsent);
                    if ($consent3 != '') {
                        $newArr[] = 'announcements';
                    }
                }
            } elseif($customerData->getCustomAttribute('account_consent_given')->getValue() == 1) {
                if (!empty($customerData->getCustomAttribute('marketingconsent'))) {
                    $updatedConsent = $customerData->getCustomAttribute('marketingconsent')->getValue();
                    $registeredConsent = explode(',', $updatedConsent);
                    $attributeCode = 'marketingconsent';
                    $consent3 = array_search('announcements', $registeredConsent);
                    if ($consent3 != '') {
                        $regArr[] = 'announcements';
                    }
                }
                else{
                    $regArr[] = '';
                }
            }
            $customerResource = $this->customerResourceFactory->create();
            /*Preparing Data for Kakao message*/
            $optedInConsent = [];
            $optedOutConsent = [];
            $finalOptIn = [];
            $finalOptOut = [];
            $optInConsent = '';
            $optOutConsent = '';
            foreach ($parameterConsent as $key => $value) {
                if (is_null($value) || $value == '')
                    unset($parameterConsent[$key]);
            }
            if ($customerData->getCustomAttribute('account_consent_given')->getValue() == 0) {
                if (
                    sizeof($parameterConsent) > sizeof($newArr) &&
                    array_intersect($parameterConsent, $newArr) != $parameterConsent
                ) {
                    if(in_array('announcements', $parameterConsent)){
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 1);
                    }else{
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 0);
                    }
                    
                    $customer->updateData($customerData);
                    $customerResource->saveAttribute($customer, 'marketingconsent_updatedat');
                    $customerResource->saveAttribute($customer, 'market_consent_given');
                    foreach ($parameterConsent as $requestconsent) {
                        if (!in_array($requestconsent, $newArr)) {
                            $optedInConsent[] = $requestconsent; //opted in consents
                        }
                    }
                } elseif (
                    sizeof($newArr) > sizeof($parameterConsent) &&
                    array_intersect($newArr, $parameterConsent) != $newArr
                ) {
                    if(in_array('announcements', $parameterConsent)){
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 1);
                    }else{
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 0);
                    }
                    $customer->updateData($customerData);
                    $customerResource->saveAttribute($customer, 'marketingconsent_updatedat');
                    $customerResource->saveAttribute($customer, 'market_consent_given');
                    foreach ($newArr as $registeredconsent) {
                        if (!in_array($registeredconsent, $parameterConsent)) {
                            $optedOutConsent[] = $registeredconsent; //opted out consents
                        }
                    }
                } elseif (
                    sizeof($newArr) == sizeof($parameterConsent) &&
                    array_intersect($parameterConsent, $newArr) != $parameterConsent
                ) {
                    if(in_array('announcements', $parameterConsent)){
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 1);
                    }else{
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 0);
                    }
                    $customer->updateData($customerData);
                    $customerResource->saveAttribute($customer, 'marketingconsent_updatedat');
                    $customerResource->saveAttribute($customer, 'market_consent_given');
                    foreach ($parameterConsent as $registeredconsent) {
                        if (!in_array($registeredconsent, $newArr)) {
                        } else {
                            $optedInConsent[] = $registeredconsent; //opted in consents
                            $optedOutConsent[] = $newArr[0]; // opted out consents
                        }
                    }
                }
            } elseif ($customerData->getCustomAttribute('account_consent_given')->getValue() == 1) {
                if (
                    sizeof($parameterConsent) > sizeof($regArr) &&
                    array_intersect($parameterConsent, $regArr) != $parameterConsent
                ) {
                    if(in_array('announcements', $parameterConsent)){
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 1);
                    }else{
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 0);
                    }
                    $customer->updateData($customerData);
                    $customerResource->saveAttribute($customer, 'marketingconsent_updatedat');
                    $customerResource->saveAttribute($customer, 'market_consent_given');
                    foreach ($parameterConsent as $requestconsent) {
                        if (!in_array($requestconsent, $regArr)) {
                            $optedInConsent[] = $requestconsent; //opted in consents
                            // print_r($optedInConsent);
                        }
                    }
                } elseif (
                    sizeof($regArr) > sizeof($parameterConsent) &&
                    array_intersect($regArr, $parameterConsent) != $regArr
                ) {
                    if(in_array('announcements', $parameterConsent)){
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 1);
                    }else{
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 0);
                    }
                    $customer->updateData($customerData);
                    $customerResource->saveAttribute($customer, 'marketingconsent_updatedat');
                    $customerResource->saveAttribute($customer, 'market_consent_given');
                    foreach ($regArr as $registeredconsent) {
                        if (!in_array($registeredconsent, $parameterConsent)) {
                            $optedOutConsent[] = $registeredconsent; //opted out consents
                        }
                    }
                } elseif (
                    sizeof($regArr) == sizeof($parameterConsent) &&
                    array_intersect($parameterConsent, $regArr) != $parameterConsent
                ) {
                    if(in_array('announcements', $parameterConsent)){
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 1);
                    }else{
                        $customerData->setCustomAttribute('marketingconsent_updatedat', $currentDate);
                        $customerData->setCustomAttribute('market_consent_given', 0);
                    }
                    $customer->updateData($customerData);
                    $customerResource->saveAttribute($customer, 'marketingconsent_updatedat');
                    $customerResource->saveAttribute($customer, 'market_consent_given');
                    foreach ($parameterConsent as $registeredconsent) {
                        if (!in_array($registeredconsent, $regArr)) {
                            $optedInConsent[] = $registeredconsent; //opted in consents
                            $optedOutConsent[] = $regArr[0]; //opted out consents
                        }
                    }
                }
            }
            $customerData->setCustomAttribute('marketingconsent', $inputConsent);
            $customerData->setCustomAttribute('account_consent_given',1);
            $customerData->setCustomAttribute('accountinfoconsent', $args['input']['consent_identifier']);
            $customer->updateData($customerData);
            $customerResource = $this->customerResourceFactory->create();
            $customerResource->saveAttribute($customer, 'account_consent_given');
            $customerResource->saveAttribute($customer, 'marketingconsent');
            $customerResource->saveAttribute($customer, 'accountinfoconsent');
            $success = true;
            $message = __('Your account has been successfully updated.');

            if (!empty($optedInConsent)) {
                foreach ($optedInConsent as $optin) {
                    $finalOptIn[] = $this->getMultiselectAttributeLabelByValue($customer, 'marketingconsent', $optin);
                    $optInConsent = implode('.', $finalOptIn);
                }
            }
            if (!empty($optedOutConsent)) {
                foreach ($optedOutConsent as $optout) {
                    $finalOptOut[] = $this->getMultiselectAttributeLabelByValue($customer, 'marketingconsent', $optout);
                    $optOutConsent = implode('.', $finalOptOut);
                }
            }
            if (!empty($customerData->getCustomAttribute('marketingconsent_updatedat'))) {
                $mobileNumber = $customerData->getCustomAttribute('mobilenumber')->getValue();
                $optInDate = $customerData->getCustomAttribute('marketingconsent_updatedat')->getValue();
                $optInOutDate = date('Y년m월d일', strtotime($optInDate));
                if ($optInConsent != '') {
                    $params = ['mktconsentaccept_date' => $optInOutDate];
                    $this->kakaoSms->sendSms($mobileNumber, $params, 'MarketingAccept_001');
                }
                if ($optOutConsent != '') {
                    $params = ['mktconsentreject_date' => $optInOutDate];
                    $this->kakaoSms->sendSms($mobileNumber, $params, 'MarketingReject_001');
                }
            }
        $result = ['success' => $success, 'message' => $message];
        return $result;
    }

    /**
     * @inheritdoc
     * @param object $customer
     * @param string $attributeCode
     * @param string $value
     */
    public function getMultiselectAttributeLabelByValue(Customer $customer, $attributeCode, $value)
    {
        $attribute = $this->attributeRepository->get(Customer::ENTITY, $attributeCode);
        if ($attribute) {
            $options = $attribute->getSource()->getAllOptions();
            $selectedLabels = [];
            $valueArray = explode(',', $value);
            foreach ($options as $option) {
                if (in_array($option['value'], $valueArray)) {
                    $selectedLabels[] = $option['label'];
                }
            }
            return implode(', ', $selectedLabels);
        }
    }
}