<?php
namespace Bat\Customer\Helper;

use Bat\Customer\Model\ResourceModel\SigunguCodeResource\CollectionFactory
        as SigunguCodeCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\AddressFactory;
use Bat\Integration\Helper\Data as IntegrationData;
use Bat\CustomerGraphQl\Helper\Data as CustomerHelper;
use Bat\Customer\Model\ChangeAddressFactory;
use Magento\Framework\Message\ManagerInterface;
use Bat\Kakao\Model\Sms as KakaoSms;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @class Data
 * Helper class for Customer
 */
class Data extends AbstractHelper
{
    /**
     * Base order frequency path
     */
    public const BASE_ORDER_FREQUENCY_PATH = 'order_frequency/customer_default_order_frequency/';

    /**
     * Default order frequency
     */
    public const DEFAULT_ORDER_FREQUENCY = 'default_order_frequency';

    /**
     * Default order frequency month
     */
    public const DEFAULT_ORDER_FREQUENCY_MONTH = 'default_order_frequency_month';

    /**
     * Default order frequency week
     */
    public const DEFAULT_ORDER_FREQUENCY_WEEK = 'default_order_frequency_week';

    /**
     * Default order frequency day
     */
    public const DEFAULT_ORDER_FREQUENCY_DAY = 'default_order_frequency_day';

    /**
     * Default order frequency fix or flexible
     */
    public const DEFAULT_ORDER_FREQUENCY_FIX_OR_FLEXIBLE = 'default_order_frequency_fix_or_flexible';

    /**
     * Default order frequency time from
     */
    public const DEFAULT_ORDER_FREQUENCY_TIME_FROM = 'default_order_frequency_time_from';

    /**
     * Default order frequency time to
     */
    public const DEFAULT_ORDER_FREQUENCY_TIME_TO = 'default_order_frequency_time_to';

    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var Config
     */
    private Config $_eavConfig;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @var CompanyManagementInterface
     */
    protected $companyManagement;

    /**
     * @var SigunguCodeCollectionFactory
     */
    private SigunguCodeCollectionFactory $sigunguCodeCollectionFactory;

    /**
     * @var AddressFactory
     */
    protected AddressFactory $addressFactory;

    /**
     * @var IntegrationData
     */
    protected $integrationData;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var ChangeAddressFactory
     */
    protected $changeAddressFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var KakaoSms
     */
    protected $kakaoSms;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $date;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $customerCollectionFactory
     * @param Config $eavConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param SigunguCodeCollectionFactory $sigunguCodeCollectionFactory
     * @param AddressFactory $addressFactory
     * @param IntegrationData $integrationData
     * @param CustomerHelper $customerHelper
     * @param ChangeAddressFactory $changeAddressFactory
     * @param ManagerInterface $messageManager
     * @param KakaoSms $kakaoSms
     * @param TimezoneInterface $date
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $customerCollectionFactory,
        Config $eavConfig,
        CustomerRepositoryInterface $customerRepository,
        CompanyRepositoryInterface $companyRepository,
        CompanyManagementInterface $companyManagement,
        SigunguCodeCollectionFactory $sigunguCodeCollectionFactory,
        AddressFactory $addressFactory,
        IntegrationData $integrationData,
        CustomerHelper $customerHelper,
        ChangeAddressFactory $changeAddressFactory,
        ManagerInterface $messageManager,
        KakaoSms $kakaoSms,
        TimezoneInterface $date
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->_eavConfig = $eavConfig;
        $this->customerRepository = $customerRepository;
        $this->companyRepository = $companyRepository;
        $this->companyManagement = $companyManagement;
        $this->sigunguCodeCollectionFactory = $sigunguCodeCollectionFactory;
        $this->addressFactory = $addressFactory;
        $this->integrationData = $integrationData;
        $this->customerHelper = $customerHelper;
        $this->changeAddressFactory = $changeAddressFactory;
        $this->messageManager = $messageManager;
        $this->kakaoSms = $kakaoSms;
        $this->date =  $date;
    }

    /**
     * Validate if outletid is already registered or not.
     *
     * @param  string $outletId
     * @return array
     */
    public function isOutletIdValidCustomer($outletId)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_id', $outletId);
        if ($collection->getSize() > 0) {
            $customer = $collection->getFirstItem();
            return $customer;
        } else {
            return $customer;
        }
    }

    /**
     * Validate if outletpin is already registered or not.
     *
     * @param  string $outletPin
     * @return array
     */
    public function isOutletPinValidCustomer($outletPin)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_pin', $outletPin);
        if ($collection->getSize() > 0) {
            $customer = $collection->getFirstItem();
            return $customer;
        } else {
            return $customer;
        }
    }

    /**
     * Getting customer collection.
     *
     * @param  string $field
     * @param  string $value
     * @return array
     */
    public function getCustomer($field, $value)
    {
        $collection = $this->customerCollectionFactory->create()
            ->addAttributeToFilter($field, $value);
        return $collection;
    }

    /**
     * Getting parent outlet collection.
     *
     * @param  int $outletId
     * @return array
     */
    public function getParentOutlet($outletId)
    {
        $collection = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('is_parent', 1)
            ->addAttributeToFilter('outlet_id', $outletId);
        return $collection;
    }

    /**
     * Return System Configuration value based on path
     *
     * @param  string $path
     * @return mixed
     */
    public function getSystemConfigValue($path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Create Customer In EDA Logs
     *
     * @param  string $message
     * @throws Zend_Log_Exception
     */
    public function logEdaCustomerUpdateRequest($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/EdaCustomer.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Return Attribute Option Label
     *
     * @param  string $attributeCode
     * @param  string $value
     * @return mixed|null
     */
    public function getAttributeLabelByValue($attributeCode, $value)
    {
        try {
            $entityType = $this->_eavConfig->getEntityType('customer');
            $attribute = $this->_eavConfig->getAttribute('customer', $attributeCode);
            $options = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    /**
     * Check If customer can be child
     *
     * @param CustomerInterface $customer
     */
    public function checkIfCustomerCanBeChild($customer)
    {
        $isParentOutlet = 0;
        $parentIsCreditCustomer = 0;
        $isCreditCustomer = 0;
        $parentOutletId = '';
        if ($customer->getCustomAttribute('is_parent')) {
            $isParentOutlet = $customer->getCustomAttribute('is_parent')->getValue();
        }
        if ($customer->getCustomAttribute('parent_outlet_id')) {
            $parentOutletId = $customer->getCustomAttribute('parent_outlet_id')->getValue();
        }
        if (!$isParentOutlet) {
            if (trim($parentOutletId) != '') {
                $parentCustomer = $this->isOutletIdValidCustomer($parentOutletId);
                if ($parentCustomer) {
                    /**
 * @var CustomerInterface $parentCustomer
*/
                    $parentCustomer = $this->customerRepository->getById($parentCustomer->getId());
                    $isParentCustomerParent = 0;
                    if ($parentCustomer->getCustomAttribute('is_parent')) {
                        $isParentCustomerParent = $parentCustomer->getCustomAttribute('is_parent')->getValue();
                    }
                    if (!$isParentCustomerParent) {
                        throw new LocalizedException(__('Parent Outlet Id is not a Parent Outlet'));
                    }
                    if ($parentCustomer->getCustomAttribute('is_credit_customer')) {
                        $parentIsCreditCustomer = $parentCustomer->getCustomAttribute('is_credit_customer')->getValue();
                    }
                    if ($customer->getCustomAttribute('is_credit_customer')) {
                        $isCreditCustomer = $customer->getCustomAttribute('is_credit_customer')->getValue();
                    }
                    if ($parentIsCreditCustomer != $isCreditCustomer) {
                        if ($isCreditCustomer) {
                            throw new LocalizedException(__('AR Customer cannot be child of Non-AR Customer'));
                        } else {
                            throw new LocalizedException(__('Non-AR Customer cannot be child of AR Customer'));
                        }
                    }
                } else {
                    throw new LocalizedException(__('Parent Outlet Id is not a valid Outlet Id'));
                }
            }
        } else {
            if (trim($parentOutletId) != '') {
                throw new LocalizedException(__('Parent Outlet cannot be a Child Outlet'));
            }
        }
    }

    /**
     * Check if customer AR type can be changed
     *
     * @param  CustomerInterface $customer
     * @param  CustomerInterface $customerOriginalData
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkIfCustomerArTypeCanBeChanged($customer, $customerOriginalData)
    {
        $isParentOutletCurrent = 0;
        $isParentOutletPrevious = 0;
        $isCreditCustomerCurrent = 0;
        $isCreditCustomerPrevious = 0;
        $OutletId = $customer->getCustomAttribute('outlet_id')->getValue();
        if ($customer->getCustomAttribute('is_credit_customer')) {
            $isCreditCustomerCurrent = $customer->getCustomAttribute('is_credit_customer')->getValue();
        }
        if ($customerOriginalData->getCustomAttribute('is_credit_customer')) {
            $isCreditCustomerPrevious = $customerOriginalData->getCustomAttribute('is_credit_customer')->getValue();
        }
        if ($customerOriginalData->getCustomAttribute('is_parent')) {
            $isParentOutletPrevious = $customerOriginalData->getCustomAttribute('is_parent')->getValue();
        }
        $childCollection = $this->getCustomer('parent_outlet_id', $OutletId);
        if ($customer->getCustomAttribute('is_parent')) {
            $isParentOutletCurrent = $customer->getCustomAttribute('is_parent')->getValue();
        }
        if ($isParentOutletPrevious == 1 && $isParentOutletCurrent == 0) {
            if ($childCollection->getSize() > 0) {
                throw new LocalizedException(
                    __(
                        'Un-assign all the Child Outlets before changing Parent outlet to Normal Outlet'
                    )
                );
            }
        }
        if ($isCreditCustomerPrevious != $isCreditCustomerCurrent) {
            if ($isParentOutletCurrent) {
                if ($childCollection->getSize() > 0) {
                    throw new LocalizedException(__('Customer AR Type Change not allowed for Parent Outlets'));
                }
            } else {
                $parentOutletId = '';
                if ($customerOriginalData->getCustomAttribute('parent_outlet_id')) {
                    $parentOutletId = $customerOriginalData->getCustomAttribute('parent_outlet_id')->getValue();
                }
                if ($parentOutletId != '') {
                    throw new LocalizedException(__('Customer AR Type Change not allowed for Child Outlets'));
                }
            }
        }
    }

    /**
     * Get Child outlets.
     *
     * @param  string $parentOutletId
     * @return array
     */
    public function getChildOutlet($parentOutletId)
    {
        $childOutlet = [];

        $parentData = $this->getCustomer('outlet_id', $parentOutletId);
        if ($parentData->getSize() > 0) {
            $customerData = $parentData->getFirstItem();
            $parentCompanyName = $this->getInfo($customerData->getId());
            $childOutlet[$customerData->getOutletId()] = $parentCompanyName;
        }

        $collection = $this->getCustomer('parent_outlet_id', $parentOutletId);

        if ($collection->getSize() > 0) {
            foreach ($collection as $data) {
                $companyName = $this->getInfo($data->getId());
                $childOutlet[$data->getOutletId()] = $companyName;
            }
        }

        return $childOutlet;
    }

    /**
     * @inheritdoc
     */
    public function getInfo($id)
    {
        $companyName = '';
        try{
            $company = $this->companyManagement->getByCustomerId($id);
            if($company){
                $companyId = $company->getId();
                $companyDetails = $this->companyRepository->get($companyId);
                $companyName =  $companyDetails->getCompanyName();
            }
        } catch (NoSuchEntityException $e) {
            return $companyName;
        } catch (LocalizedException $e) {
            return $companyName;
        }
        return $companyName;
    }

    /**
     * Return customer collection for account termination
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerCollectionForTermination()
    {
        $collection = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('deactivated_at', ['neq' => 0])
            ->addAttributeToFilter('approval_status', ['neq' => 9]);
        return $collection;
    }

    /**
     * Return customer terminated status
     *
     * @param  CustomerInterface $customer
     * @return bool
     */
    public function getCustomerTerminatedStatus($customer)
    {
        $status = false;
        if ($customer->getCustomAttribute('approval_status')) {
            $disapprovalStatus = $customer->getCustomAttribute(
                'approval_status'
            )->getValue();
            if ($disapprovalStatus == 9) {
                $status = true;
            }
        }
        return $status;
    }

    /**
     * Get city based on sigungu code
     *
     * @param  string $postalCode
     * @return string
     */
    public function getCity($postalCode)
    {
        $city = "-";
        $sigunguCode = $this->sigunguCodeCollectionFactory->create();
        $sigunguCode->addFieldToFilter('sigungu_code', ['eq' => $postalCode]);
        if ($sigunguCode->getSize()) {
            $area = $sigunguCode->getFirstItem();
            if ($area->getCity() != '') {
                $city = $area->getCity();
            }
        }
        return $city;
    }

    /**
     * Get customer default billing address
     *
     * @param  array $customer
     * @return String
     */
    public function getCustomerDefaultShippingAddress($customer)
    {
        $outletAddress = '';
        if ($customer->getDefaultShipping()) {
            $shippingAddress = $this->addressFactory->create()->load($customer->getDefaultShipping());
            $addressData = $shippingAddress->getData();
            $outletAddress = $addressData['street'] . ', ' . $addressData['postcode'];
        }
        return $outletAddress;
    }

    /**
     * Get customer Data by customer id
     *
     * @param  array $customerId
     * @return String
     */
    public function getCustomerById($customerId)
    {
        return $this->customerRepository->getById($customerId);
    }

    /**
     * Send Change address kakao
     *
     * @param string $mobileNumber
     * @param int    $outletId
     */
    public function sendAddressChangeKakao($mobileNumber, $outletId)
    {
        $key = $this->saveEncryptUrl($outletId, "address_change");
        $url = $this->customerHelper->getAddressChangeUrl();
        $addressChangeLink = $url . '&id=' . $key;

        $this->kakaoSms->sendSms(
            $mobileNumber,
            ['changeaddress_link' => $addressChangeLink],
            'AddressChangeRequest_001'
        );
        $this->messageManager->addSuccess(
            __(
                "Kakao Message Sent Successfully"
            )
        );
    }

    /**
     * Delete Old Url
     *
     * @param int $customerId
     */
    public function deleteOldUrl($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $outletId = $customer->getCustomAttribute('outlet_id')->getValue();
        $collection = $this->changeAddressFactory->create()->getCollection();
        $dataModel = $this->changeAddressFactory->create();
        $collection->addFieldToFilter('outlet_id', ['finset' => $outletId])
            ->addFieldToFilter('url_type', ['finset' => 'address_change']);
        $records = $collection->getData();
        foreach ($records as $record) {
            $dataModel->load($record['entity_id']);
            $dataModel->delete();
        }
    }
      /**
       * Send Rejected change address kakao
       *
       * @param int $customerId
       */
    public function sendRejectedAddressChangeKakao($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
        $outletId = $customer->getCustomAttribute('outlet_id')->getValue();

        $encryptData = $this->saveEncryptUrl($outletId, "address_change");
        $url = $this->customerHelper->getAddressChangeUrl();
        $addressChangeLink = $url . '&id=' . $encryptData;

        $this->messageManager->addSuccess(
            __(
                "Address change Request Rejected and Kakao Message Sent Successfully"
            )
        );
        $this->kakaoSms->sendSms(
            $mobileNumber,
            ['changeaddressreject_link' => $addressChangeLink],
            'AddressChangeReject_001'
        );
    }

    /**
     * Delete Used ForgotPinPassword Url
     *
     * @param string $outletID
     */
    public function deleteForgotPinPasswordUrl($outletId)
    {
        $collection = $this->changeAddressFactory->create()->getCollection();
        $dataModel = $this->changeAddressFactory->create();
        $collection->addFieldToFilter('outlet_id', $outletId)
            ->addFieldToFilter('url_type','forgot_set_pinpassword');
        $records = $collection->getData();
        foreach ($records as $record) {
            $dataModel->load($record['entity_id']);
            $dataModel->delete();
        }
    }

    /**
     * Delete Used SetPinPassword Url
     *
     * @param string $outletId
     */
    public function deleteSetPinPasswordUrl($outletId)
    {
        $collection = $this->changeAddressFactory->create()->getCollection();
        $dataModel = $this->changeAddressFactory->create();
        $collection->addFieldToFilter('outlet_id', $outletId)
            ->addFieldToFilter('url_type', 'registration_set_pinpassword');
        $records = $collection->getData();
        foreach ($records as $record) {
            $dataModel->load($record['entity_id']);
            $dataModel->delete();
        }
    }
    /**
     * Save encrypt value of outlet id in the bat_url table
     *
     * @param string $outletId
     * @param string $type
     */
    public function saveEncryptUrl($outletId, $type)
    {
        $collection = $this->changeAddressFactory->create()->getCollection()
            ->addFieldToFilter('outlet_id', ['eq' => $outletId])
            ->addFieldToFilter('url_type', ['eq' => $type]);

        $key = $this->getEncryptKey($outletId);
        $data = [
            'outlet_id' => $outletId,
            'url_type' => $type,
            'urlkey' => $key
        ];

        if ($collection->getSize() > 0) {
            $data['entity_id'] = $collection->getFirstItem()->getEntityId();
        }

        $dataModel = $this->changeAddressFactory->create();
        $dataModel->setData($data);
        $dataModel->save();

        return $key;
    }

    /**
     * Get encrypted value of outlet id with date time appended
     *
     * @param string $outletId
     */
    public function getEncryptKey($outletId)
    {
        $outletId .= "," . $this->date->date()->format('Y-m-d H:i:s');
        return $this->integrationData->encryptData($outletId);
    }

     /**
      * Get Default Order Frequency
      */
    public function getDefaultOrderFrequency()
    {
        return $this->getSystemConfigValue(self::BASE_ORDER_FREQUENCY_PATH.self::DEFAULT_ORDER_FREQUENCY);
    }

    /**
     * Get Default Order Frequency Month
     */
    public function getDefaultOrderFrequencyMonth()
    {
        return $this->getSystemConfigValue(self::BASE_ORDER_FREQUENCY_PATH.self::DEFAULT_ORDER_FREQUENCY_MONTH);
    }

    /**
     * Get Default Order Frequency Week
     */
    public function getDefaultOrderFrequencyWeek()
    {
        return $this->getSystemConfigValue(self::BASE_ORDER_FREQUENCY_PATH.self::DEFAULT_ORDER_FREQUENCY_WEEK);
    }

    /**
     * Get Default Order Frequency Day
     */
    public function getDefaultOrderFrequencyDay()
    {
        return $this->getSystemConfigValue(self::BASE_ORDER_FREQUENCY_PATH.self::DEFAULT_ORDER_FREQUENCY_DAY);
    }

    /**
     * Get Default Order Frequency Fix Or Flexible
     */
    public function getDefaultOrderFrequencyFixOrFlexible()
    {
        return $this->getSystemConfigValue(
            self::BASE_ORDER_FREQUENCY_PATH.self::DEFAULT_ORDER_FREQUENCY_FIX_OR_FLEXIBLE
        );
    }

     /**
      * Get Default Order Frequency Time From
      */
    public function getDefaultOrderFrequencyTimeFrom()
    {
        return $this->getSystemConfigValue(self::BASE_ORDER_FREQUENCY_PATH.self::DEFAULT_ORDER_FREQUENCY_TIME_FROM);
    }

    /**
     * Get Default Order Frequency Time To
     */
    public function getDefaultOrderFrequencyTimeTo()
    {
        return $this->getSystemConfigValue(self::BASE_ORDER_FREQUENCY_PATH.self::DEFAULT_ORDER_FREQUENCY_TIME_TO);
    }

    /**
     * Generate Unique Batch ID
     *
     * @param string $customerId
     * @return string
     */
    public function getUniqueBatchId($customerId)
    {
        $dateTime = $this->date->date()->format('Y-m-d H:i:s');
        return $customerId.strtotime($dateTime);
    }
}
