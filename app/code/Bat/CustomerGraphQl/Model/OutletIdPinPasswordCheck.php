<?php
namespace Bat\CustomerGraphQl\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Bat\Integration\Helper\Data as IntegrationHelper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Bat\Kakao\Model\Sms as KakaoSms;

class OutletIdPinPasswordCheck
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerRegistry $customerRegistry
     * @param EncryptorInterface $encryptor
     * @param IntegrationHelper $integrationHelper
     * @param KakaoSms $kakaoSms
     */
    public function __construct(
        Data $helper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerRegistry $customerRegistry,
        EncryptorInterface $encryptor,
        IntegrationHelper $integrationHelper,
        KakaoSms $kakaoSms
    ) {
        $this->helper = $helper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerRegistry = $customerRegistry;
        $this->encryptor = $encryptor;
        $this->integrationHelper = $integrationHelper;
        $this->kakaoSms = $kakaoSms;
    }

    /**
     * Data validation and update
     *
     * @param array $data
     * @throws GraphQlInputException
     */
    public function execute($data)
    {
        try {
            $this->vaildateData($data);
            $isCustomerSetPinFirstTime = 0;
            $data['outletId'] = $this->integrationHelper->decryptData($data['outletId']);
            $decryptFields = explode(",", $data['outletId']);
            $data['outletId'] = $decryptFields[0];
            $response = $this->helper->isOutletIdValidCustomer($data['outletId']);
            if (!empty($response)) {
                $customerId = $response->getId(); // here assign your customer id
                $customer = $this->customerRepositoryInterface->getById($customerId);
                $customerCustomAttributes = $customer->getCustomAttributes();
                if (isset($customerCustomAttributes['approval_status'])) {
                    $customerApprovalStatus = $customerCustomAttributes['approval_status'];
                    if ($customerApprovalStatus->getAttributecode() == "approval_status") {
                       $approvalStatus = $customer->getCustomAttribute(
                            'approval_status'
                        )->getValue();
                        if (in_array($approvalStatus, [0, 2, 5, 3, 9])) {
                            return [
                                'success' => false,
                                'message' => 'Your customer account is not approved.',
                                'is_account_terminated' => false
                            ];
                        }
                    }
                }
                if (
                    $customer->getCustomAttribute('is_migrated')
                    && $customer->getCustomAttribute('is_migrated')->getValue() == 1
                ) {
                    if (
                        !$customer->getCustomAttribute('outlet_pin')
                        || $customer->getCustomAttribute('outlet_pin')->getValue() == ""
                    ) {
                        if ($data['consent'] != '') {
                            $customer->setCustomAttribute('consentform', $data['consent']);
                            $this->customerRepositoryInterface->save($customer);
                            $explodeConsent = explode(',', $data['consent']);
                            if ($customer->getCustomAttribute('mobilenumber')) {
                                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                            }
                            if (isset($explodeConsent)) {
                                if (in_array('announcements', $explodeConsent)) {
                                    $currentTime = date('Y-m-d');
                                    $customer->setCustomAttribute('marketingconsent_updatedat',$currentTime);
                                    $customer->setCustomAttribute('market_consent_given',1);
                                    $this->customerRepositoryInterface->save($customer);
                                    $optInDate = date('Y년m월d일', strtotime($currentTime));
                                    $params = ['mktconsentaccept_date' => $optInDate];
                                    $this->kakaoSms->sendSms($mobileNumber, $params, 'MarketingAccept_001');
                                } else {
                                    $currentTime = date('Y-m-d');
                                    $customer->setCustomAttribute('market_consent_given',0);
                                    $this->customerRepositoryInterface->save($customer);
                                    $optOutDate = date('Y년m월d일', strtotime($currentTime));
                                    $params = ['mktconsentreject_date' => $optOutDate];
                                    $this->kakaoSms->sendSms($mobileNumber, $params, 'MarketingReject_001');
                                }
                            }
                        } else {
                            throw new GraphQlInputException(__('Please specify Consent Values'));
                        }
                    }
                }
                $terminatedStatus = $this->helper->getCustomerTerminatedStatus($customer);
                if ($terminatedStatus) {
                    return ['success' => false, 'message' => '', 'is_account_terminated' => true];
                }
                if ($customer->getCustomAttribute('outlet_pin') && $customer->getCustomAttribute('outlet_pin')->getValue() == '') {

                    $isCustomerSetPinFirstTime = 1;
                }
                $customerSecure = $this->customerRegistry->retrieveSecureData($customerId);
                $customerSecure->setRpToken(null);
                $customerSecure->setRpTokenCreatedAt(null);
                $customerSecure->setPasswordHash($this->encryptor->getHash($data['password'], true));
                $customer->setCustomAttribute('outlet_pin', base64_encode($data['pin']));
                $this->customerRepositoryInterface->save($customer);

                /* Kakao SMS for customer first login */
                if ($customer->getCustomAttribute('mobilenumber')) {
                    if ($isCustomerSetPinFirstTime == 1) {
                        $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                        $outletName = ($this->helper->getInfo($customerId)) ? $this->helper->getInfo($customerId) : '';
                        $params = ['outlet_name' => $outletName, 'outlet_id' => $data['outletId']];
                        $this->kakaoSms->sendSms($mobileNumber, $params, 'ExistingFirstLogin_001');
                    }
                }

            } else {
                throw new GraphQlInputException(__('Not found outlet Id. Enter valid Outlet Id'));
            }
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        $this->helper->deleteSetPinPasswordUrl($data['outletId']);
        $this->helper->deleteForgotPinPasswordUrl($data['outletId']);
        return ['success' => true, 'outlet_id' => $response->getOutletId(), 'message' => __('Successfully updated your password and pin.')];
    }

    /**
     * Handle bad request.
     *
     * @param array $data
     * @throws LocalizedException
     */
    private function vaildateData($data)
    {
        if (!isset($data['outletId'])) {
            throw new GraphQlInputException(__('Outlet Id value is required'));
        }
        if (!isset($data['password'])) {
            throw new GraphQlInputException(__('Password value is required'));
        } elseif (isset($data['password']) && ($data['password'] == '')) {
            throw new GraphQlInputException(__('Password value is required'));
        }
        if (!isset($data['pin'])) {
            throw new GraphQlInputException(__('Pin value required'));
        } elseif (strlen(trim($data['pin'])) != 6) {
            throw new GraphQlInputException(__('Only 6-digit pin required'));
        }
    }
}
