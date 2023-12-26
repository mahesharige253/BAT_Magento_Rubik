<?php

namespace Bat\Customer\Model;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Bat\BulkOrder\Model\Resolver\CartDetails;

/**
 * @class RejectedCustomerData
 */
class RejectedCustomerData
{

    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @var CompanyManagementInterface
     */
    protected $companyManagement;

    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * Constructor
     * @param CollectionFactory $customerCollectionFactory
     * @param Data $dataHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Registry $registry
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param CartDetails $cartDetails
     */
    public function __construct(
        CollectionFactory $customerCollectionFactory,
        Data $dataHelper,
        CustomerRepositoryInterface $customerRepository,
        Registry $registry,
        CompanyRepositoryInterface $companyRepository,
        CompanyManagementInterface $companyManagement,
        CartDetails $cartDetails
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
        $this->companyRepository = $companyRepository;
        $this->companyManagement = $companyManagement;
        $this->cartDetails = $cartDetails;
    }

    /**
     * Function to delete rejected customer
     */
    public function deleteRejectedCustomerData()
    {
        $emptyDate = '0000-00-00 00:00:00';
        $duration = $this->dataHelper->getSystemConfigValue(
            'bat_customer_rejection/general/account_rejection_required_duration'
        );
        if($duration != '' && $duration >= 0) { 
            $this->addLog('Date:'.date('Y-m-d', strtotime('-'.$duration.' day'))); //exit;
            $collection = $this->customerCollectionFactory->create()
                    ->addAttributeToFilter('approval_status', 2)
                    ->addAttributeToFilter(
                        'registration_rejected_at',
                        ['lt' => date('Y-m-d H:i:s', strtotime('-'.$duration.' day'))]
                    )
                    ->addAttributeToFilter('registration_rejected_at', ['neq' => $emptyDate]);
                    $this->addLog('Count of RejectedCustomer Data :'.count($collection));

                    if ($collection->getSize()) {
                        foreach ($collection as $customer) {
                            $this->deleteCustomer($customer);
                        }
                    } else {
                        $this->addLog('No data to delete rejected customers');
                    }
        } else {
            $this->addLog('Duration days are not specified');
        }     
    }

     /**
      * Function to delete customer
      *
      * @param string $customer
      * @throws Zend_Log_Exception
      */
    public function deleteCustomer($customer)
    {
        try {
            $this->addLog('========Customer Data Starts=========');
            $this->addLog('Customer Id :'.$customer->getId());
            $customerData = $this->customerRepository->getById($customer->getId());
            $outletId = ($customerData->getCustomAttribute('outlet_id'))
                        ?$customerData->getCustomAttribute('outlet_id')->getValue():'';
            $mobilenumber = ($customerData->getCustomAttribute('mobilenumber'))
                        ?$customerData->getCustomAttribute('mobilenumber')->getValue():'';
            $secondaryEmail = ($customerData->getCustomAttribute('secondary_email'))
                            ?$customerData->getCustomAttribute('secondary_email')->getValue():'';
            $firstname = $customerData->getFirstname();
            $sapCode = ($customerData->getCustomAttribute('sap_outlet_code'))
                        ?$customerData->getCustomAttribute('sap_outlet_code')->getValue():'';
            $businessLicenceNumber = ($customerData->getCustomAttribute('bat_business_license_number'))
                                    ?$customerData->getCustomAttribute('bat_business_license_number')->getValue():'';
            $virtualAccountNo = ($customerData->getCustomAttribute('virtual_account'))
                                ?$customerData->getCustomAttribute('virtual_account')->getValue():'';
            $bankCode = ($customerData->getCustomAttribute('virtual_bank'))
                        ?$customerData->getCustomAttribute('virtual_bank')->getValue():'';
            if ($bankCode != '') {
                $bankName = $this->cartDetails->getAttributeLabelByValue('virtual_bank', 'customer', $bankCode);
            } else {
                $bankName = '';
            }

            $customerAddress = $this->dataHelper->getCustomerDefaultShippingAddress($customerData);
            
            $sigunguCode = ($customerData->getCustomAttribute('sigungu_code'))
                            ?$customerData->getCustomAttribute('sigungu_code')->getValue():'';
            $this->addLog('Outlet Id :'.$outletId);
            $this->addLog('Mobilenumber :'.$mobilenumber);
            $this->addLog('Secondary Email :'.$secondaryEmail);
            $this->addLog('Firstname :'.$firstname);
            $this->addLog('Sap Outlet Code :'.$sapCode);
            $this->addLog('Virtual Account No :'.$virtualAccountNo);
            $this->addLog('Virtual Bank :'.$bankName);
            $this->addLog('Business Licence Number :'.$businessLicenceNumber);
            $this->addLog('Customer Address :'.$customerAddress);
            $this->registry->register('isSecureArea', true);

            //Deleting the company associated with customer
            try {
                $company = $this->companyManagement->getByCustomerId($customerData->getId());
                if($company) {
                    $companyId = $company->getId();
                    $companyData = $this->companyRepository->get($companyId);
                    $this->addLog('Customer associated company Id :'.$companyId);
                    $this->addLog('Company Name : '.$companyData->getCompanyName());
                    $this->companyRepository->deleteById($companyId);
                }
            } catch (\Exception $e) {
                $this->addLog('Account Company Exception : '.$e->getMessage());
            }
            
            //Deleting the customer
            $this->customerRepository->delete($customerData);
            $this->addLog('Deleted Customer Id :'.$customer->getId());
            $this->registry->unregister('isSecureArea');
            $this->addLog('========Customer Data Ends=========');
        } catch (\Exception $e) {
            $this->addLog('Account Rejected CustomerDeletion Cron : '.$e->getMessage());
        }
    }

    /**
     * Add Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addLog($message)
    {
        $logEnable = $this->dataHelper->getSystemConfigValue(
            'bat_customer_rejection/general/log_enabled'
        );
        if ($logEnable) {
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/rejected_customer_cron.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($message);
        }
    }
}
