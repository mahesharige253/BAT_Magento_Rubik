<?php

namespace Bat\Customer\Model;

use Bat\Customer\Helper\Data;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Bat\Customer\Model\ResourceModel\EdaCustomersResource\CollectionFactory as EdaCustomerCollectionFactory;
use Magento\Company\Api\Data\CompanyInterface;

/**
 * @class SendCustomerDetails
 * Create/Update Customers in EDA
 */
class SendCustomerDetails
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var Data
     */
    protected Data $dataHelper;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var EdaCustomerCollectionFactory
     */
    private EdaCustomerCollectionFactory $edaCustomerCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $date;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $dataHelper
     * @param LoggerInterface $logger
     * @param EdaCustomerCollectionFactory $edaCustomerCollectionFactory
     * @param TimezoneInterface $date
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $dataHelper,
        LoggerInterface $logger,
        EdaCustomerCollectionFactory $edaCustomerCollectionFactory,
        TimezoneInterface $date,
        StoreManagerInterface $storeManager
    ) {
        $this->customerRepository = $customerRepository;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->edaCustomerCollectionFactory = $edaCustomerCollectionFactory;
        $this->date =  $date;
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare Payload for EDA Customer create/update
     *
     * @param CustomerInterface $customer
     * @param CompanyInterface $company
     * @param string $updateType
     * @param string $channel
     */
    public function formatCustomerData($customer, $company, $updateType, $channel)
    {
        if ($updateType == 'new') {
            $transactionType = 'CREATE';
        } else {
            $transactionType = 'UPDATE';
        }
        $result = [];
        $customerId = $customer->getId();
        $createdAt = $customer->getCreatedAt();
        $countryCode = $this->dataHelper->getSystemConfigValue('general/country/default');
        $result['header']['batchId'] = $this->dataHelper->getUniqueBatchId($customerId);
        $result['header']['transactionType'] = $transactionType;
        $result['header']['creationDate'] = $this->date->date($createdAt)->format('Ymd');
        $result['header']['countryCode'] = $countryCode;
        $result['header']['companyCode'] = 'KR12';
        $result['header']['channel'] = $channel;
        $customerData = [];
        $outletName = $company->getCompanyName();
        if (strlen($outletName) > 35) {
            $splitOutletName = mb_str_split($outletName);
            $outletSize = 0;
            $outletName1 = '';
            $outletName2 = '';
            $outletName3 = '';
            foreach ($splitOutletName as $name) {
                $outletSize = $outletSize + strlen($name);
                if ($outletSize < 35) {
                    $outletName1 = $outletName1.$name;
                    $customerData['outletName'] = $outletName1;
                } elseif ($outletSize < 70) {
                    $outletName2 = $outletName2.$name;
                    $customerData['outletName2'] = $outletName2;
                } elseif ($outletSize < 80) {
                    $outletName3 = $outletName3.$name;
                    $customerData['outletName3'] = $outletName3;
                }
            }
        } else {
            $customerData['outletName'] = $outletName;
        }

        $customerData['m2OutletId'] = $customer->getCustomAttribute('outlet_id')->getValue();
        $customerData['customerAccountGroup'] = $this->getCustomerAttributeValue(
            $customer,
            'customer_account_group'
        );
        $customerData['taxCode'] = $this->getCustomerAttributeValue($customer, 'tax_code');
        $customerData['gstNumber'] = $this->getCustomerAttributeValue($customer, 'gst_number');
        $customerData['outletEmail'] = $this->getCustomerAttributeValue($customer, 'secondary_email');
        $accountStatus = 'I';
        if ($customer->getCustomAttribute('approval_status')) {
            $accountStatus = $customer->getCustomAttribute('approval_status')->getValue();
            $accountStatus = $this->dataHelper->getAttributeLabelByValue('approval_status', $accountStatus);
            $approveStatusList = ['Approved','Closure Under Review','Closure Rejected','Closure Refund In-Progress','Closure Collection In-Progress','Closure New Request'];
            if (in_array($accountStatus, $approveStatusList)) {
                $accountStatus = 'A';
            } else {
                $accountStatus = 'I';
            }
        }
        $customerData['status'] = $accountStatus;
        $customerData['languageCode'] = 'KO';
        $customerData['countryCode'] = $countryCode;
        $customerData['salesOrg'] = $this->getCustomerAttributeValue($customer, 'sales_organization');
        $customerData['division'] = $this->getCustomerAttributeValue($customer, 'division');
        $customerData['pricingProcedure'] = $this->getCustomerAttributeValue($customer, 'pricing_procedure');
        $customerData['customerGroup'] = $this->getCustomerAttributeValue($customer, 'customer_group');
        $customerData['deliveryPriority'] = $this->getCustomerAttributeValue($customer, 'delivery_priority');
        $customerData['shippingCondition'] = $this->getCustomerAttributeValue($customer, 'shipping_condition');
        $customerData['currencyCode'] = $this->storeManager->getStore()->getBaseCurrencyCode();
        $customerData['salesOffice'] = $this->getCustomerAttributeValue($customer, 'sales_office');
        $customerData['salesGroup'] = $this->getCustomerAttributeValue($customer, 'sales_group');
        $customerData['deliveryPlant'] = $this->getCustomerAttributeValue($customer, 'delivery_plant');
        $customerData['accountGroup'] = $this->getCustomerAttributeValue($customer, 'account_group');
        $customerData['incoterm1'] = $this->getCustomerAttributeValue($customer, 'incoterm_one');
        $customerData['incoterm2'] =  $this->getCustomerAttributeValue($customer, 'incoterm_two');
        $customerData['paymentTerm'] = $this->getCustomerAttributeValue($customer, 'payment_term');
        $customerData['custGroup5'] = $this->getCustomerAttributeValue($customer, 'cust_group_five');
        $customerData['outletOwnerName'] = $customer->getFirstname();
        $customerData['paymentType'] = $this->getCustomerAttributeValue($customer, 'payment_type');
        $customerData['businessLicenceType'] = $this->getCustomerAttributeValue($customer, 'business_licence_type');
        $customerData['businessLicenceItem'] = $this->getCustomerAttributeValue($customer, 'business_licence_item');
        if ($customer->getCustomAttribute('approval_status')) {
            if ($customer->getCustomAttribute('approval_status')->getValue() == 4) {
                $customerData['virtualBankId'] = $customer->getCustomAttribute('virtual_bank_new')->getValue();
                $customerData['virtualBankActNo'] = $customer->getCustomAttribute('virtual_account_new')->getValue();
            } else {
                $customerData['virtualBankId'] = $customer->getCustomAttribute('virtual_bank')->getValue();
                $customerData['virtualBankActNo'] = $customer->getCustomAttribute('virtual_account')->getValue();
            }
        }
        $customerData['createdDate'] = $createdAt;
        $customerData['updatedDate'] = $customer->getUpdatedAt();
        $preferredCom = $this->getCustomerAttributeValue($customer, 'preferred_communication');
        $customerData['preferredCom'] = ($preferredCom != '') ? $preferredCom : 'TEL';
        $distributionChannel = $this->getCustomerAttributeValue($customer, 'distribution_channel');
        $customerData['distributionChannel'] = ($distributionChannel != '') ? $distributionChannel : '01';
        $sapOutletId = $this->getCustomerAttributeValue($customer, 'sap_outlet_code');
        if (trim($sapOutletId) != '') {
            $customerData['sapOutletId'] = $sapOutletId;
        }
        $sapVendorCode = $this->getCustomerAttributeValue($customer, 'sap_vendor_code');
        if (trim($sapVendorCode) != '' && $transactionType != 'CREATE') {
            $customerData['sapVendorCode'] = $this->getCustomerAttributeValue($customer, 'sap_vendor_code');
        }
        $customerData['outletOwnerTelephone'] = $customer->getCustomAttribute('mobilenumber')->getValue();
        $customerData['outletTelephone'] = $company->getTelephone();
        $additionalGstNumber = $this->getCustomerAttributeValue($customer, 'additional_gst_number');
        if ($additionalGstNumber != '') {
            $customerData['additionalGSTNumber'] = $additionalGstNumber;
        }
        $isHqOutlet = (boolean)$this->getCustomerAttributeValue($customer, 'is_parent');
        $isHqOutletId = $this->getCustomerAttributeValue($customer, 'parent_outlet_id');
        if (!$isHqOutlet && $isHqOutletId != '' && trim($isHqOutletId) != '') {
            $customerData['isHQOutlet'] = false;
            $customerData['hqOutletId'] = $isHqOutletId;
        } else {
            $customerData['isHQOutlet'] = true;
        }
        $isMarketConsentGiven = (boolean)$this->getCustomerAttributeValue($customer, 'market_consent_given');
        $customerData['isMarketConsentGiven'] = $isMarketConsentGiven;
        if ($isMarketConsentGiven) {
            $marketConsentDate = $this->getCustomerAttributeValue($customer, 'marketingconsent_updatedat');
            if ($marketConsentDate != '') {
                $customerData['marketConsentDate'] = $marketConsentDate;
            }
        }
        $ownerGender = $this->getCustomerAttributeValue($customer, 'owner_gender');
        $ownerBirthYear = $this->getCustomerAttributeValue($customer, 'owner_birth_year');
        if ($ownerGender != '' && trim($ownerGender) != '') {
            $customerData['ownerGender'] = $ownerGender;
        }
        if ($ownerBirthYear != '' && trim($ownerBirthYear) != '') {
            $customerData['ownerBirthYear'] = $ownerBirthYear;
        }
        $frequencyDay = $this->getCustomerFrequencyDay($customer, 'order_frequency_day');
        $frequencyWeek = $this->getCustomerFrequencyWeek($customer, 'order_frequency_week');
        $frequencyMonth = $this->getCustomerFrequencyMonth($customer, 'order_frequency_month');
        if (trim($frequencyDay) != '') {
            $customerData['visitDay'] = $frequencyDay;
        }
        if (trim($frequencyWeek) != '') {
            $customerData['visitWeek'] = $frequencyWeek;
        }
        if (trim($frequencyMonth) != '') {
            $customerData['visitMonth'] = $frequencyMonth;
        }
        $customerData['addresses'] = $this->getCustomerAddress($customer);
        $result['customers'][] = $customerData;
        $result['footer']['recordCount'] = 1;
        return $result;
    }

    /**
     * Return customer address
     *
     * @param CustomerInterface $customer
     */
    public function getCustomerAddress($customer)
    {
        $customerAddress = [];
        if ($customer->getAddresses() != null) {
            foreach ($customer->getAddresses() as $address) {
                $street = implode(' ', $address->getStreet());
                if (strlen($street) > 35) {
                    $splitAddressData = mb_str_split($street);
                    $size = 0;
                    $addressLine1 = '';
                    $addressLine2 = '';
                    $addressLine3 = '';
                    $addressLine4 = '';
                    foreach ($splitAddressData as $data) {
                        $size = $size + strlen($data);
                        if ($size < 35) {
                            $addressLine1 = $addressLine1.$data;
                            $customerAddressData['addressLine1'] = $addressLine1;
                        } elseif ($size < 70) {
                            $addressLine2 = $addressLine2.$data;
                            $customerAddressData['addressLine2'] = $addressLine2;
                        } elseif ($size < 105) {
                            $addressLine3 = $addressLine3.$data;
                            $customerAddressData['addressLine3'] = $addressLine3;
                        } elseif ($size < 140) {
                            $addressLine4 = $addressLine4.$data;
                            $customerAddressData['addressLine4'] = $addressLine4;
                        }
                    }
                } else {
                    $customerAddressData['addressLine1'] = $street;
                }
                $customerAddressData['city'] = $address->getCity();
                $customerAddressData['postalCode'] = $address->getPostcode();
                $customerAddress[] = $customerAddressData;
            }
        }
        return $customerAddress;
    }

    /**
     * Return EDA create/update customers collection
     *
     * @param int $maxFailuresAllowed
     * @return ResourceModel\EdaCustomersResource\Collection
     */
    public function getEdaCustomerCollection($maxFailuresAllowed)
    {
        return $this->edaCustomerCollectionFactory->create()->addFieldToSelect('*')
            ->addFieldToFilter('failure_attempts', ['lt'=>$maxFailuresAllowed])
            ->addFieldToFilter('customer_sent', ['eq'=>0]);
    }

    /**
     * Return EDA customer for update
     *
     * @param string $customerId
     * @param string $channel
     * @return \Magento\Framework\DataObject
     */
    public function getEdaCustomerForUpdate($customerId, $channel)
    {
        return $this->edaCustomerCollectionFactory->create()->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', ['eq'=>$customerId])
            ->addFieldToFilter('channel', ['eq'=>$channel])
            ->getFirstItem();
    }

    /**
     * Return customer attribute value
     *
     * @param CustomerInterface $customer
     * @param string $attribute
     * @return mixed|string
     */
    public function getCustomerAttributeValue($customer, $attribute)
    {
        $value = '';
        if ($customer->getCustomAttribute($attribute)) {
            $value = $customer->getCustomAttribute($attribute)->getValue();
        }
        return $value;
    }

    /**
     * Return Customer Frequency Day
     *
     * @param CustomerInterface $customer
     * @param string $attribute
     * @return string
     */
    public function getCustomerFrequencyDay($customer, $attribute)
    {
        $day = '';
        $value = $this->getCustomerAttributeValue($customer, $attribute);
        if ($value == 'Monday') {
            $day = "MON";
        } elseif ($value == "Tuesday") {
            $day = "TUE";
        } elseif ($value == "Wednesday") {
            $day = "WED";
        } elseif ($value == "Thursday") {
            $day = "THU";
        } elseif ($value == "Friday") {
            $day = "FRI";
        }
        return $day;
    }

    /**
     * Return Customer Frequency Week
     *
     * @param CustomerInterface $customer
     * @param string $attribute
     * @return string
     */
    public function getCustomerFrequencyWeek($customer, $attribute)
    {
        $week = '';
        $value = $this->getCustomerAttributeValue($customer, $attribute);
        if ($value == 'every') {
            $week = "EW";
        } elseif ($value == "even") {
            $week = "VW";
        } elseif ($value == "odd") {
            $week = "OW";
        } elseif ($value == "week_one") {
            $week = "1W";
        } elseif ($value == "week_two") {
            $week = "2W";
        } elseif ($value == "week_three") {
            $week = "3W";
        } elseif ($value == "week_four") {
            $week = "4W";
        }
        return $week;
    }

    /**
     * Return Customer Frequency Month
     *
     * @param CustomerInterface $customer
     * @param string $attribute
     * @return string
     */
    public function getCustomerFrequencyMonth($customer, $attribute)
    {
        $month = '';
        $value = $this->getCustomerAttributeValue($customer, $attribute);
        if ($value == 'every') {
            $month = "EM";
        } elseif ($value == "even") {
            $month = "VM";
        } elseif ($value == "odd") {
            $month = "OM";
        }
        return $month;
    }
}
