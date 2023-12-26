<?php

namespace Bat\CustomerGraphQl\Model;

use Magento\Integration\Model\Oauth\TokenFactory;
use Bat\Customer\Helper\Data;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Customer;
use Bat\ConcurrentSessions\Model\Device;
use Bat\PasswordHistory\Model\ResourceModel\UsedPassword\CollectionFactory as UsedPasswordCollectionFactory;

class ValidatePinPassword
{
    /**
     * @var TokenFactory
     */
    protected $tokenModelFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var AccountManagement
     */
    protected $accountmanagement;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CustomerRepositoryInterface;
     */
    protected $customerRepository;

     /**
      * @var Customer;
      */
    protected $customerModel;

    /**
     * @var Device
     */
    protected $device;

    /**
     * @var UsedPasswordCollectionFactory
     */
    private UsedPasswordCollectionFactory $usedPasswordCollectionFactory;

    /**
     * @param TokenFactory $tokenModelFactory
     * @param Data $helper
     * @param AccountManagement $accountmanagement
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param Customer $customerModel
     * @param Device $device
     * @param UsedPasswordCollectionFactory $usedPasswordCollectionFactory
     */
    public function __construct(
        TokenFactory $tokenModelFactory,
        Data $helper,
        AccountManagement $accountmanagement,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        Customer $customerModel,
        Device $device,
        UsedPasswordCollectionFactory $usedPasswordCollectionFactory
    ) {
        $this->tokenModelFactory = $tokenModelFactory;
        $this->helper = $helper;
        $this->accountmanagement = $accountmanagement;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        $this->customerModel = $customerModel;
        $this->device = $device;
        $this->usedPasswordCollectionFactory = $usedPasswordCollectionFactory;
    }

    /**
     * Data validation and genrate token
     *
     * @param array $data
     * @throws GraphQlInputException
     */
    public function loginCustomerWithPinPassword($data)
    {
        $genrateToken = '';
        $mobileNumber = '';
        $customerToken = $this->tokenModelFactory->create();
        $response = $this->helper->isOutletIdValidCustomer($data['outletId']);
        if (!empty($response)) {
            $mobileNumber = $response->getMobilenumber();
        }
        $maxAttemptsValue = $this->scopeConfig->getValue(
            'customer/password/lockout_failures',
            ScopeInterface::SCOPE_STORE,
        );
        if (!empty($response)) {
            $customer = $this->customerRepository->getById($response->getId());
            $isNewStatus = $this->isNotApprovedCustomer($customer);
            if ($isNewStatus) {
                return [
                    'token' => '',
                    'mobilenumber' => '',
                    'message' => __('Your customer account is not approved.'),
                    'maximum_attemps' => true,
                    'is_account_terminated' => false
                ];
            }
            $terminatedStatus = $this->helper->getCustomerTerminatedStatus($customer);
            if ($terminatedStatus) {
                return [
                    'token' => '',
                    'mobilenumber' => '',
                    'message' => __('Account is terminated'),
                    'maximum_attemps' => false,
                    'is_account_terminated' => true
                ];
            }
            $extensionAttributes = $customer->getExtensionAttributes();
            if ($extensionAttributes->getCompanyAttributes()->getStatus() == 0) {
                return [
                    'token' => '',
                    'mobilenumber' => '',
                    'message' => __('Your account is locked, You have reached maximum number of login attempts'),
                    'maximum_attemps' => false,
                    'is_account_terminated' => false
                ];
            }

            $attempts = $response->getFailuresNum();
            if ($response->getLockExpires()) {
                $val = $response->getLockExpires();
                $lockExpires = new \DateTime($val);
            } else {
                $lockExpires = '';
            }
        }
        if (!empty($response)) {
            if (isset($data['pin'])) {
                if ($response['outlet_pin'] == '' || $response['outlet_pin'] == null) {
                    $lastPin = $this->checkIfPinExistsInHistory($response->getId());
                    if ($lastPin != '') {
                        $response['outlet_pin'] = $lastPin;
                    }
                }
                if ($response['outlet_pin'] == base64_encode($data['pin'])) {
                    if (empty($lockExpires) || $lockExpires < new \DateTime()) {
                        $genrateToken = $customerToken->createCustomerToken($response->getId())->getToken();
                        if ($genrateToken != '') {
                            $customer = $this->customerModel->load($response->getId());
                            $customer->setFailuresNum(0);
                            $customer->save();
                        }
                    } else {
                        return [
                            'token' => '',
                            'mobilenumber' => '',
                            'message' =>
                                __('Your account is locked, You have reached maximum number of login attempts'),
                            'maximum_attemps' => true,
                            'is_account_terminated' => false
                        ];
                    }
                } else {
                    try {
                        $this->accountmanagement->authenticate($response->getEmail(), base64_encode($data['pin']));
                    } catch (\Exception $e) {
                        ++$attempts;
                        if ($attempts >= $maxAttemptsValue) {
                            $this->checkLockedStatus($maxAttemptsValue, $attempts, $lockExpires);
                        }

                        $remainingattempts = $maxAttemptsValue - $attempts;
                        if ($remainingattempts > 0) {
                            return [
                                'token' => '',
                                'mobilenumber' => '',
                                'message' => __(
                                    'Invalid login Credentials,You have remaining attempts of %1 out of %2',
                                    $remainingattempts,
                                    $maxAttemptsValue
                                ),
                                'maximum_attemps' => false,
                                'is_account_terminated' => false
                            ];
                        } else {
                            return [
                                'token' => '',
                                'mobilenumber' => '',
                                'message' => __(
                                    'Your account is locked, You have reached maximum number of login attempts'
                                ),
                                'maximum_attemps' => true,
                                'is_account_terminated' => false
                            ];
                        }

                    }
                }
            } else {
                if (isset($data['password'])) {
                    try {
                        $this->accountmanagement->authenticate($response->getEmail(), $data['password']);
                        $genrateToken = $customerToken->createCustomerToken($response->getId())->getToken();
                        if ($genrateToken != '') {
                            $customer = $this->customerModel->load($response->getId());
                            $customer->setFailuresNum(0);
                            $customer->save();
                        }
                    } catch (\Exception $e) {
                        ++$attempts;
                        if ($attempts >= $maxAttemptsValue) {
                            $this->checkLockedStatus($maxAttemptsValue, $attempts, $lockExpires);
                        }
                        $remainingattempts = $maxAttemptsValue - $attempts;
                        if ($remainingattempts > 0) {
                            return [
                                'token' => '',
                                'mobilenumber' => '',
                                'message' => __(
                                    'Invalid login Credentials,You have remaining attempts of %1 out of %2',
                                    $remainingattempts,
                                    $maxAttemptsValue
                                ),
                                'maximum_attemps' => false,
                                'is_account_terminated' => false
                            ];
                        } else {
                            return [
                                'token' => '',
                                'mobilenumber' => '',
                                'message' => __(
                                    'Your account is locked, You have reached maximum number of login attempts'
                                ),
                                'maximum_attemps' => true,
                                'is_account_terminated' => false
                            ];
                        }
                    }
                } else {
                    return [
                        'token' => '',
                        'mobilenumber' => '',
                        'message' => __('Invalid login details'),
                        'maximum_attemps' => false,
                        'is_account_terminated' => false
                    ];
                }
            }
        } else {
            return [
                'token' => '',
                'mobilenumber' => '',
                'message' => __('Invalid login details'),
                'maximum_attemps' => false,
                'is_account_terminated' => false
            ];
        }
        $mobileNumber = ($genrateToken) ? $mobileNumber : '';
        $isDeviceNew = $this->device->isNewDevice($response->getId(), $data['device_id']);

        return [
            'token' => $genrateToken,
            'mobilenumber' => $mobileNumber,
            'message' => 'Success',
            'maximum_attemps' => false,
            'is_account_terminated' => false,
            'is_new_device' => $isDeviceNew
        ];
    }

    /**
     * Check locked status
     *
     * @param string $maxAttemptsValue
     * @param string $attempts
     * @param object $lockExpires
     * @throws GraphQlInputException
     */
    public function checkLockedStatus($maxAttemptsValue, $attempts, $lockExpires = null)
    {
        if (empty($lockExpires)) {
            return [
                'token' => '',
                'mobilenumber' => '',
                'message' => __('Your Account is locked, You have reached maximum number of login attempts'),
                'maximum_attemps' => true,
                'is_account_terminated' => false
            ];
        }
        if ($lockExpires > new \DateTime()) {
            return [
                'token' => '',
                'mobilenumber' => '',
                'message' => __('Your Account is locked, You have reached maximum number of login attempts'),
                'maximum_attemps' => true,
                'is_account_terminated' => false
            ];
        } else {
            $attempts = $attempts - $maxAttemptsValue;
            $remainingattempts = $maxAttemptsValue - $attempts;
            return [
                'token' => '',
                'mobilenumber' => '',
                'message' => __(
                    'Invalid login Credentials,You have remaining attempts of %1 out of %2',
                    $remainingattempts,
                    $maxAttemptsValue
                ),
                'maximum_attemps' => false,
                'is_account_terminated' => false
            ];
        }
    }

    /**
     * Is Not Approved customer
     *
     * @param object $customer
     * @return boolean
     */
    public function isNotApprovedCustomer($customer)
    {
        $customerCustomAttributes = $customer->getCustomAttributes();
        if (isset($customerCustomAttributes['approval_status'])) {
            $customerApprovalStatus = $customerCustomAttributes['approval_status'];
            if ($customerApprovalStatus->getAttributecode() == "approval_status") {
                $approvalStatus = $customer->getCustomAttribute(
                    'approval_status'
                )->getValue();
                if (in_array($approvalStatus, [0, 2, 3, 5])) {
                    return true;
                }
            }
        }
    }

    /**
     * Return customer last used pin
     *
     * @param int $customerId
     * @return string
     */
    public function checkIfPinExistsInHistory($customerId)
    {
        $lastPin = '';
        try {
            $usedPassWord = $this->usedPasswordCollectionFactory->create()
                ->addFieldToFilter('customer_id', ['eq'=>$customerId]);
            if ($usedPassWord->getSize()) {
                $lastUsedPin = $usedPassWord->getLastItem();
                $lastPin = $lastUsedPin->getOutletPin();
                if ($lastPin != '') {
                    $customer = $this->customerRepository->getById($customerId);
                    $customer->setCustomAttribute('outlet_pin', $lastPin);
                    $this->customerRepository->save($customer);
                }
            }
        } catch (\Exception $e) {
            $lastPin = '';
        }
        return $lastPin;
    }
}
