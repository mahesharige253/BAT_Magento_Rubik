<?php
namespace Bat\Customer\Observer;

use Bat\Customer\Model\EdaCustomersFactory;
use Bat\Customer\Model\ResourceModel\EdaCustomersResource;
use Bat\Customer\Model\SendCustomerDetails;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Customer\Helper\Data;
use Bat\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Bat\CustomerGraphQl\Helper\Data as CustomerHelperData;
use Bat\Integration\Helper\Data as IntegrationHelperData;
use Bat\QuoteGraphQl\Model\Resolver\VabInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @class CustomerSaveAfter
 * Observer to create/update customers to EDA table
 */
class CustomerSaveAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var EdaCustomersFactory
     */
    private EdaCustomersFactory $edaCustomersFactory;

    /**
     * @var EdaCustomersResource
     */
    private EdaCustomersResource $edaCustomersResource;

    /**
     * @var SendCustomerDetails
     */
    private SendCustomerDetails $sendCustomerDetails;

    /**
     * @var CustomerFactory
     */
    protected CustomerFactory $customerFactory;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var Data
     */
    protected Data $helperData;

    /**
     * @var ExtractCustomerData
     */
    protected ExtractCustomerData $extractCustomerData;

    /**
     * @var CustomerHelperData
     */
    protected CustomerHelperData $customerHelperData;

    /**
     * @var IntegrationHelperData
     */
    protected IntegrationHelperData $integrationHelperData;

      /**
       * @var VabInfo
       */
    protected VabInfo $vbaInfo;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @param LoggerInterface $logger
     * @param EdaCustomersFactory $edaCustomersFactory
     * @param EdaCustomersResource $edaCustomersResource
     * @param SendCustomerDetails $sendCustomerDetails
     * @param CustomerFactory $customerFactory
     * @param KakaoSms $kakaoSms
     * @param Data $helperData
     * @param ExtractCustomerData $extractCustomerData
     * @param CustomerHelperData $customerHelperData
     * @param IntegrationHelperData $integrationHelperData
     * @param VabInfo $vbaInfo;
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        LoggerInterface $logger,
        EdaCustomersFactory $edaCustomersFactory,
        EdaCustomersResource $edaCustomersResource,
        SendCustomerDetails $sendCustomerDetails,
        CustomerFactory $customerFactory,
        KakaoSms $kakaoSms,
        Data $helperData,
        ExtractCustomerData $extractCustomerData,
        CustomerHelperData $customerHelperData,
        IntegrationHelperData $integrationHelperData,
        VabInfo $vbaInfo,
        TimezoneInterface $timezoneInterface
    ) {
        $this->logger = $logger;
        $this->edaCustomersFactory = $edaCustomersFactory;
        $this->edaCustomersResource = $edaCustomersResource;
        $this->sendCustomerDetails = $sendCustomerDetails;
        $this->customerFactory = $customerFactory;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->extractCustomerData = $extractCustomerData;
        $this->customerHelperData = $customerHelperData;
        $this->integrationHelperData = $integrationHelperData;
        $this->vbaInfo = $vbaInfo;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Create/Update customers to EDA table
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        try {
            /** @var CustomerInterface $customer */
            $customer = $observer->getCustomerDataObject();
            $websiteId = $customer->getWebsiteId();
            /** @var CustomerInterface $customerOriginalData */
            $customerOriginalData = $observer->getOrigCustomerDataObject();
            $customerId = $customer->getId();
            $approvalStatusCurrent = '';
            $approvalStatusPrevious = '';
            $mobileNumber = '';

            if ($customer->getCustomAttribute('approval_status')) {
                $approvalStatusCurrent = $customer->getCustomAttribute('approval_status')->getValue();
            }

            if ($customer->getCustomAttribute('mobilenumber')) {
                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            }

            if ($customerOriginalData) {
                if ($customerOriginalData->getCustomAttribute('approval_status')) {
                    $approvalStatusPrevious = $customerOriginalData->getCustomAttribute('approval_status')->getValue();
                }
                if ($approvalStatusCurrent == 1 && $approvalStatusPrevious == 4) {
                    $customerFactory = $this->customerFactory->create()->load($customerId);
                    $customerDataModel = $customerFactory->getDataModel();
                    $customerDataModel->setWebsiteId($websiteId);
                    $customerDataModel->setCustomAttribute('is_vba_change_approved', 1);
                    if (($customerDataModel->getCustomAttribute('virtual_bank_new') && $customerDataModel->getCustomAttribute('virtual_account_new'))) {
                        $bankCode = $customer->getCustomAttribute('virtual_bank_new')->getValue();
                        $accountNumber = $customer->getCustomAttribute('virtual_account_new')->getValue();
                        $customerDataModel->setCustomAttribute('virtual_bank', $bankCode);
                        $customerDataModel->setCustomAttribute('virtual_account', $accountNumber);
                    }
                    $customerDataModel->setCustomAttribute('virtual_bank_new', '');
                    $customerDataModel->setCustomAttribute('virtual_account_new', '');
                    $customerFactory->updateData($customerDataModel);
                    $customerFactory->save();
                    if ($mobileNumber) {
                        /* Kakao SMS For VBA change approve by admin */
                        $this->kakaoSms->sendSms($mobileNumber, [], 'VBAChange_001');
                    }

                }

                $outletOwnerName = '';
                $outletId = '';
                $outletAddress = '';

                if ($mobileNumber) {
                    $outletAddress = $this->helperData->getCustomerDefaultShippingAddress($customer);
                    if ($customer->getCustomAttribute('outlet_id')) {
                        $outletId = $customer->getCustomAttribute('outlet_id')->getValue();
                    }

                    if ($customer->getFirstname()) {
                        $outletOwnerName = $customer->getFirstname();
                    }

                    // Kakao SMS For Customer registration approve
                    if ($approvalStatusCurrent == 1 && $approvalStatusPrevious == 5) {
                        $outletName = $this->helperData->getInfo($customerId);
                        $encryptData = $this->helperData->saveEncryptUrl($outletId, "registration_set_pinpassword");
                        $url = $this->customerHelperData->getSetPasswordPinUrl();
                        $newpinpasswordLink = $url.'&id='.$encryptData;
                        $params = [
                            'outlet_name' => $outletName,
                            'owner_name' => $outletOwnerName,
                            'outlet_address' => $outletAddress,
                            'mobilephonenumber' => $mobileNumber,
                            'outlet_id' => $outletId,
                            'newpinpassword_link' => $newpinpasswordLink
                        ];
                        $this->kakaoSms->sendSms($mobileNumber, $params, 'RegistrationApprove_002');
                    }

                    if ($approvalStatusCurrent == 2 && $approvalStatusPrevious != 2) {
                        /* Kakao SMS For Customer status Rejected */
                        $rejectReason = '';
                        $resubmitLink = '';
                        $reasonImplode = [];
                        if ($this->customerHelperData->getRegistrationReSubmitUrl() != '') {
                            $resubmitLink = $this->customerHelperData->getRegistrationReSubmitUrl();
                        }

                        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');

                        $customerFactory = $this->customerFactory->create()->load($customerId);
                        $customerDataModel = $customerFactory->getDataModel();
                        $customerDataModel->setWebsiteId($websiteId);
                        $customerDataModel->setCustomAttribute('registration_rejected_at', $currentDate);
                        $customerFactory->updateData($customerDataModel);
                        $customerFactory->save();

                        if ($customer->getCustomAttribute('rejected_fields')) {
                            $rejectedFields = $customer->getCustomAttribute('rejected_fields')->getValue();
                            $extractRejectedFields = explode(',', $rejectedFields);

                            foreach ($extractRejectedFields as $key => $value) {
                                $reason = $this->extractCustomerData->
                                    getAttributeLabelByValue('rejected_fields', $value);
                                $reasonImplode[] = (string)$reason;
                            }
                            $rejectReason = implode(',', $reasonImplode);
                        }
                        $params = [
                            'registerationreject_reason' => $rejectReason,
                            'registrationresubmit_link' => $resubmitLink
                        ];
                        $this->kakaoSms->sendSms($mobileNumber, $params, 'RegistrationReject_001');
                    }

                    if ($approvalStatusCurrent == 9 && $approvalStatusPrevious != 9) {
                        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');

                        $customerFactory = $this->customerFactory->create()->load($customerId);
                        $customerDataModel = $customerFactory->getDataModel();
                        $customerDataModel->setWebsiteId($websiteId);
                        $customerDataModel->setCustomAttribute('customer_termination_at', $currentDate);
                        $customerFactory->updateData($customerDataModel);
                        $customerFactory->save();
                    }

                    /* Kakao SMS for Account Closure request rejected by Admin */
                    if ($approvalStatusCurrent == 8 && $approvalStatusPrevious != 8) {
                        $outletName = $this->helperData->getInfo($customerId);
                        $params = [
                            'outlet_name' => $outletName,
                            'outlet_address' => $outletAddress
                        ];
                        $this->kakaoSms->sendSms($mobileNumber, $params, 'ClosingReject_001');
                    }

                    /* Kakao SMS for Account Closure request approved by Admin */
                    if ($approvalStatusCurrent == 7 && $approvalStatusPrevious != 7) {
                        $outletName = $this->helperData->getInfo($customerId);
                        $params = [
                            'outlet_name' => $outletName,
                            'outlet_address' => $outletAddress
                        ];
                        $this->kakaoSms->sendSms($mobileNumber, $params, 'ClosingComplete_001');
                    }

                    //Address change request reject & approve.
                    if ($approvalStatusCurrent == 13 && $approvalStatusPrevious == 12) {
                        /* Kakao SMS for reject address change request approved by Admin */
                        $this->helperData->sendRejectedAddressChangeKakao($customerId);
                    } elseif ($approvalStatusCurrent == 1 && $approvalStatusPrevious == 12) {
                        $this->helperData->deleteOldUrl($customerId);
                        $customerFactory = $this->customerFactory->create()->load($customerId);
                        $customerDataModel = $customerFactory->getDataModel();
                        $customerDataModel->setWebsiteId($websiteId);
                        $customerDataModel->setCustomAttribute('is_vba_change_approved', 2);
                        $customerFactory->updateData($customerDataModel);
                        $customerFactory->save();
                        /* Kakao SMS for address change request approved by Admin */
                        $this->kakaoSms->sendSms($mobileNumber, [], 'AddressChangeApprove_001');
                    }
                }
            }

            $channel = 'SWIFTPLUS';
            $sapOutletIdCurrent = '';
            $allowedStatusForEdaPush = [1,4,5,6,7,8,9,10,11,12,13,14];

            if ($customer->getCustomAttribute('sap_outlet_code')) {
                $sapOutletIdCurrent = $customer->getCustomAttribute('sap_outlet_code')->getValue();
            }
            $type = 'new';
            if ($customerOriginalData) {
                if (in_array($approvalStatusPrevious, $allowedStatusForEdaPush)) {
                    $type = 'update';
                }
            }
            if (in_array($approvalStatusCurrent, $allowedStatusForEdaPush)) {
                if ($type == 'update' && $channel == 'SWIFTPLUS') {
                    if ($customer->getCustomAttribute('customer_sap_confirmation_status')) {
                        $customerConfirmed = $customer->getCustomAttribute(
                            'customer_sap_confirmation_status'
                        )->getValue();
                        if (!$customerConfirmed || $sapOutletIdCurrent == '') {
                            return $this;
                        }
                    }
                }
                $edaCustomer = $this->sendCustomerDetails->getEdaCustomerForUpdate($customerId, $channel);
                if (!empty($edaCustomer->getData())) {
                    $edaCustomer->setUpdateType($type);
                    $edaCustomer->setFailureAttempts(0);
                    $edaCustomer->setCustomerSent(0);
                    $edaCustomer->setChannel($channel);
                    $this->edaCustomersResource->save($edaCustomer);
                } else {
                    $edaCustomer = $this->edaCustomersFactory->create();
                    $edaCustomer->setData(
                        ['customer_id' => $customer->getId(), 'update_type' => $type, 'channel' => $channel]
                    );
                    $this->edaCustomersResource->save($edaCustomer);
                }
                $channel = 'OMS';
                $allowedStatusForOmsPush = [1,4,6,7,8,9,10,11,12,13,14];
                if(in_array($approvalStatusCurrent, $allowedStatusForOmsPush)){
                    $edaCustomerOms = $this->sendCustomerDetails->getEdaCustomerForUpdate($customerId, $channel);
                    if (!empty($edaCustomerOms->getData())) {
                        $edaCustomerOms->setUpdateType($type);
                        $edaCustomerOms->setFailureAttempts(0);
                        $edaCustomerOms->setCustomerSent(0);
                        $edaCustomerOms->setChannel($channel);
                        $this->edaCustomersResource->save($edaCustomerOms);
                    } else {
                        $edaCustomerOms = $this->edaCustomersFactory->create();
                        $edaCustomerOms->setData(
                            ['customer_id' => $customerId, 'update_type' => $type, 'channel' => $channel]
                        );
                        $this->edaCustomersResource->save($edaCustomerOms);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info('Create/Update to EDA Customer table failed ' . $e->getMessage());
        }
        return $this;
    }
}
