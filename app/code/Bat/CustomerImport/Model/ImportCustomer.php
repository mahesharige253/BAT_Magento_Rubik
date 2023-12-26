<?php

namespace Bat\CustomerImport\Model;

use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Bat\VirtualBank\Helper\Data as VbaHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Model\AddressFactory;
use Bat\Customer\Model\SigunguCodeFactory;
use Bat\CustomerGraphQl\Model\Resolver\CreateCustomer;
use Magento\Framework\Api\DataObjectHelper;
use Bat\Customer\Helper\Data as CustomerData;

class ImportCustomer
{

    /**
     * @var Filesystem
     */
    private Filesystem $_filesystem;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $_storeManager;

    /**
     * @var Csv
     */
    private Csv $csvProcessor;

    /**
     * @var VbaHelper
     */
    private VbaHelper $vbaHelper;

    /**
     * @var UploaderFactory
     */
    private UploaderFactory $fileUploaderFactory;

    /**
     * @var CustomerInterfaceFactory
     */
    protected CustomerInterfaceFactory $customerInterfaceFactory;

    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $encryptorInterface;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepositoryInterface;

    /**
     * @var CompanyRepositoryInterface
     */
    private CompanyRepositoryInterface $companyRepository;

    /**
     * @var CompanyInterface
     */
    private CompanyInterface $companyInterface;

    /**
     * @var AddressFactory
     */
    private AddressFactory $addressFactory;

    /**
     * @var SigunguCodeFactory
     */
    private SigunguCodeFactory $sigunguCodeFactory;

    /**
     * @var CreateCustomer
     */
    protected CreateCustomer $outletCustomer;

    /**
     * @var DataObjectHelper
     */
    private DataObjectHelper $objectHelper;

    /**
     * @var CustomerData
     */
    private $customerHelper;

    /**
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param Csv $csvProcessor
     * @param VbaHelper $vbaHelper
     * @param UploaderFactory $fileUploaderFactory
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param EncryptorInterface $encryptorInterface
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyInterface $companyInterface
     * @param AddressFactory $addressFactory
     * @param SigunguCodeFactory $sigunguCodeFactory
     * @param CreateCustomer $createCustomer
     * @param DataObjectHelper $objectHelper
     * @param CustomerData $customerHelper
     */
    public function __construct(
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        Csv $csvProcessor,
        VbaHelper $vbaHelper,
        UploaderFactory $fileUploaderFactory,
        CustomerInterfaceFactory $customerInterfaceFactory,
        EncryptorInterface $encryptorInterface,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CompanyRepositoryInterface $companyRepository,
        CompanyInterface $companyInterface,
        AddressFactory $addressFactory,
        SigunguCodeFactory $sigunguCodeFactory,
        CreateCustomer $createCustomer,
        DataObjectHelper $objectHelper,
        CustomerData $customerHelper
    ) {
        $this->_filesystem                  = $filesystem;
        $this->_storeManager                = $storeManager;
        $this->csvProcessor                 = $csvProcessor;
        $this->vbaHelper                    = $vbaHelper;
        $this->fileUploaderFactory          = $fileUploaderFactory;
        $this->customerInterfaceFactory     = $customerInterfaceFactory;
        $this->encryptorInterface           = $encryptorInterface;
        $this->customerRepositoryInterface  = $customerRepositoryInterface;
        $this->companyRepository            = $companyRepository;
        $this->companyInterface             = $companyInterface;
        $this->addressFactory               = $addressFactory;
        $this->sigunguCodeFactory           = $sigunguCodeFactory;
        $this->outletCustomer               = $createCustomer;
        $this->objectHelper                 = $objectHelper;
        $this->customerHelper               = $customerHelper;
    }

    /**
     * Create Customer
     *
     * @param string $filePath
     */
    public function customers($filePath)
    {
        $importData = $this->csvProcessor->getData($filePath);
        $heading = $importData[0];
        $importedSuccessfully = 0;
        $result = [];
        if (!empty($importData)) {
            if ($this->haederValidation($heading)) {
                $errors = [];
                $rowCounter = 1;
                $accountNumbersProcessed = [];

                foreach (array_slice($importData, 1) as $key => $value) {
                        $customerData = [];
                        $customerData = array_combine($importData[0], $value);
                    try {
                        $rowCounter++;
                                  
                        $this->createCustomer($customerData);
                        $importedSuccessfully++;
                    } catch (\Exception $e) {
                        $errors[] = 'Record'.'-'.$rowCounter.' :'.$e->getMessage();
                        $message = __('Record'.'-'.$rowCounter.' :'.$e->getMessage());
                        $result[] = ['message' =>  (string)$message,
                                    'success' => false
                                    ];
                    }
                }
                if ($importedSuccessfully) {
                    $message = __("Successfully Imported %1 Records", $importedSuccessfully);
                    $result[] = ['message' => (string)$message,
                                        'success' => true
                                        ];
                }

            } else {
                 $result = __("Please provide requried and valid columns");
            }
        } else {
            $result = __("Please varify data");
        }
        return $result;
    }

    /**
     * Create company
     *
     * @param object $companyData
     */
    protected function createCompany($companyData)
    {
        // Code to create the company and assign to customer
        $companyRepo = $this->companyRepository;
        $companyObj = $this->companyInterface;
        $dataObj = $this->objectHelper;
        $dataObj->populateWithArray(
            $companyObj,
            $companyData,
            \Magento\Company\Api\Data\CompanyInterface::class
        );

        $companyRepo->save($companyObj);
    }

    /**
     * Create Address
     *
     * @param array $addressData
     * @param int $customerId
     */
    protected function createAddress(array $addressData, int $customerId)
    {
        $address = $this->addressFactory->create();
        $address->setCustomerId($customerId)
            ->setFirstname($addressData['name'])
            ->setLastname('-')
            ->setCountryId('KR')
            ->setPostcode($addressData['postcode'])
            ->setCity($addressData['city'])
            ->setTelephone($addressData['mobilenumber'])
            ->setStreet($addressData['street'])
            ->setStreet1($addressData['street1'])
            ->setStreet2($addressData['street2'])
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('1');
        $address->save();
        return $address->getId();
    }

    /**
     * Header validation
     *
     * @param array $heading
     */
    public function haederValidation($heading)
    {
        $headerData = ['SAP_CODE', 'ECC_VENDOR_CD', 'ECC_CUST_ACC_GRP',
                     'CUST_NAME1', 'CUST_NAME2', 'CUST_NAME3',
                     'CUST_CITY', 'CUST_ADDR1', 'CUST_ADDR2',
                     'CUST_ADDR3', 'CUST_ADDR4', 'AS_CUST_POCODE',
                     'AS_CUST_TEL1', 'AS_CUST_FAX', 'GST_NO',
                     'CUST_STATUS', 'SORT_FIELD', 'ECC_SEARCH_TERM2',
                     'PREFERRED_COM', 'ECC_SALES_ORG', 'ECC_DIST_CH',
                     'ECC_DIV', 'ECC_PRICINGPRC', 'ECC_CUST_GRP',
                     'ECC_DELIVERY_VL', 'ECC_SHIPPING_CND', 'ECC_CURRENCY',
                     'ECC_SALES_OFFICE', 'ECC_SALES_GRP', 'ECC_DELIVERY_PLNT',
                     'ECC_ACCOUNT_GRP', 'ECC_INCOTERM1', 'ECC_INCOTERM2',
                     'PAYMENT_TERM', 'ECC_CUST_GRP5', 'CUST_OWNER_LNAME',
                     'CUST_OWNER_FNAME', 'CUST_OWNER_TEL', 'CUST_EMAIL',
                     'SWIFT_PAYMENT_TYPE', 'GST_NO_ADD', 'BUS_TYPE', 'BUS_ITEM',
                     'HQ_OUTLET_FLAG', 'HQ_OUTLET_ID', 'BANK_ID', 'ACCOUNT_NO',
                     'IS_DM_ACCEPT', 'DM_ACCEPT_DATE', 'OWNER_GENDER',
                     'OWNER_BIRTH_YEAR', 'ECC_POD_IND'];
        if (array_diff($headerData, $heading) == array_diff($heading, $headerData)) {
            return true;
        }
        return false;
    }

    /**
     * Return virtual account collection
     *
     * @param String $bank
     * @return array
     */
    public function getVirtualAccountCollectionOnBank($bank)
    {
        $accountCollection = $this->accountCollectionFactory->create()
            ->addFieldToSelect('vba_no')
            ->addFieldToFilter('bank_code', ['eq'=> $bank]);
        return $accountCollection;
    }

    /**
     * Create Customer
     *
     * @param array $customerData
     */
    public function createCustomer($customerData)
    {
         //Customer create
        $customer = $this->customerInterfaceFactory->create();
        $customer->setWebsiteId(1);
        $outletId = $this->outletCustomer->getOutletId();
        $email = $outletId.'@'.$outletId.'.com';
        $customer->setEmail($email);
        
        $customer->setFirstname($customerData['CUST_OWNER_LNAME']);
        $customer->setLastname("-");
        $customer->setCustomAttribute('outlet_id', $outletId);
        $customer->setCustomAttribute('mobilenumber', $customerData['AS_CUST_TEL1']);
        $customer->setCustomAttribute('virtual_account', $customerData['ACCOUNT_NO']);
        $customer->setCustomAttribute('virtual_bank', $customerData['BANK_ID']);
        $customer->setCustomAttribute('secondary_email', $customerData['CUST_EMAIL']);
        $customer->setCustomAttribute('owner_gender', $customerData['OWNER_GENDER']);
        $customer->setCustomAttribute('bat_business_license_number', $customerData['GST_NO']);
        $customer->setCustomAttribute('gst_number', $customerData['GST_NO']);
        $customer->setCustomAttribute('additional_gst_number', $customerData['GST_NO_ADD']);
        $customer->setCustomAttribute('owner_birth_year', str_replace(',', '', $customerData['OWNER_BIRTH_YEAR']));
        $customer->setCustomAttribute('parent_outlet_id', $customerData['HQ_OUTLET_ID']);
        if ($customerData['HQ_OUTLET_FLAG'] == 'Y') {
            $customer->setCustomAttribute('is_credit_customer', 1);
        } else {
            $customer->setCustomAttribute('is_credit_customer', 0);
        }

        /** Customer default values set */
        $customer->setCustomAttribute('is_migrated', 1);
        $customer->setCustomAttribute('approval_status', 1);
        $customer->setCustomAttribute('bat_order_frequency', $this->customerHelper->getDefaultOrderFrequency());
        $customer->setCustomAttribute('order_frequency_month', $this->customerHelper->getDefaultOrderFrequencyMonth());
        $customer->setCustomAttribute('order_frequency_week', $this->customerHelper->getDefaultOrderFrequencyWeek());
        $customer->setCustomAttribute('order_frequency_day', $this->customerHelper->getDefaultOrderFrequencyDay());
        $customer->setCustomAttribute(
            'fix_flexible_order_day',
            $this->customerHelper->getDefaultOrderFrequencyFixOrFlexible()
        );
        $customer->setCustomAttribute(
            'order_frequency_time_from',
            $this->customerHelper->getDefaultOrderFrequencyTimeFrom()
        );
        $customer->setCustomAttribute(
            'order_frequency_time_to',
            $this->customerHelper->getDefaultOrderFrequencyTimeTo()
        );
        $customer->setCustomAttribute('language_code', 'KO');
        $customer->setCustomAttribute('bat_country_code', 'KR');
        /** Customer default values set */
        $customer->setCustomAttribute('payment_type', $customerData['SWIFT_PAYMENT_TYPE']);
        $customer->setCustomAttribute('cust_group_five', $customerData['ECC_CUST_GRP5']);
        $customer->setCustomAttribute('sales_organization', $customerData['ECC_SALES_ORG']);
        $customer->setCustomAttribute('division', $customerData['ECC_DIV']);
        $customer->setCustomAttribute('pricing_procedure', $customerData['ECC_PRICINGPRC']);
        $customer->setCustomAttribute('customer_group', $customerData['ECC_CUST_GRP']);
        $customer->setCustomAttribute('delivery_priority', $customerData['ECC_DELIVERY_VL']);
        $customer->setCustomAttribute('shipping_condition', $customerData['ECC_SHIPPING_CND']);
        $customer->setCustomAttribute('sales_group', $customerData['ECC_SALES_GRP']);
        $customer->setCustomAttribute('account_group', $customerData['ECC_ACCOUNT_GRP']);
        $customer->setCustomAttribute('incoterm_one', $customerData['ECC_INCOTERM1']);
        $customer->setCustomAttribute('incoterm_two', $customerData['ECC_INCOTERM2']);
        $customer->setCustomAttribute('preferred_communication', $customerData['PREFERRED_COM']);
        $customer->setCustomAttribute('distribution_channel', $customerData['ECC_DIST_CH']);
        $customer->setCustomAttribute('customer_account_group', $customerData['ECC_CUST_ACC_GRP']);
        $customer->setCustomAttribute('sap_outlet_code', $customerData['SAP_CODE']);
        if ($customerData['SAP_CODE'] != '') {
            $customer->setCustomAttribute('customer_sap_confirmation_status', 1);
        }
        $customer->setCustomAttribute('payment_term', $customerData['PAYMENT_TERM']);
        $customer->setCustomAttribute('sap_vendor_code', $customerData['ECC_VENDOR_CD']);
        $customer->setCustomAttribute('marketingconsent_updatedat', $customerData['DM_ACCEPT_DATE']);
        $sigunguCode = $this->sigunguCodeFactory->create()
                        ->getCollection()->addFieldToFilter('tax_code', $customerData['CUST_CITY'])
                            ->load()->getFirstItem();

        if (!empty($sigunguCode->getData())) {
            $customer->setCustomAttribute('sigungu_code', $sigunguCode['sigungu_code']);
        }
        $customer->setCustomAttribute('tax_code', $customerData['CUST_CITY']);
        $customer->setCustomAttribute('depot', $customerData['ECC_SALES_OFFICE']);
        $customer->setCustomAttribute('sales_office', $customerData['ECC_SALES_OFFICE']);
        $customer->setCustomAttribute('delivery_plant', $customerData['ECC_DELIVERY_PLNT']);

        $customer->setCustomAttribute('business_licence_type', $customerData['BUS_TYPE']);
        $customer->setCustomAttribute('business_licence_item', $customerData['BUS_ITEM']);
        
        $hashedPassword = $this->encryptorInterface->getHash('6*y.mB<64ni^i£4K', true);

        $this->customerRepositoryInterface->save($customer, $hashedPassword);
        $address = ['name' => $customerData['CUST_OWNER_LNAME'],
                    'city' => $sigunguCode['city'],
                    'postcode' => $customerData['AS_CUST_POCODE'],
                    'mobilenumber' => $customerData['AS_CUST_TEL1'],
                    'street' => $customerData['CUST_ADDR1'],
                    'street1' => $customerData['CUST_ADDR2'],
                    'street2' => $customerData['CUST_ADDR3'],
                    'fax' => $customerData['AS_CUST_FAX']
                    ];
        $customerInfo = $this->customerHelper->isOutletIdValidCustomer($outletId);
        $this->createAddress($address, $customerInfo->getId());
        $companyData = [
                        "company_name" => $customerData['CUST_NAME1'],
                        "company_email" => $customerData['CUST_EMAIL'],
                        "street" => $customerData['CUST_ADDR1'],
                        "city" => $sigunguCode['city'],
                        "country_id" => "KR",
                        "postcode" => $customerData['AS_CUST_POCODE'],
                        "telephone" => $customerData['AS_CUST_TEL1'],
                        "super_user_id" => $customerInfo->getId(),
                        "customer_group_id" => 1
                    ];
        $this->createCompany($companyData);
    }
}
