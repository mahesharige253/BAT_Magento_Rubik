<?php

namespace Bat\Customer\Model;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollection;
use Magento\Sales\Model\OrderFactory;

/**
 * @class RejectedCustomerData
 */
class TerminatedCustomerData
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
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var OrderCollection
     */
    protected $orderCollection;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * Constructor
     * @param CollectionFactory $customerCollectionFactory
     * @param Data $dataHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Registry $registry
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param CustomerRegistry $customerRegistry
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerFactory $customerFactory
     * @param OrderCollection $orderCollection
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        CollectionFactory $customerCollectionFactory,
        Data $dataHelper,
        CustomerRepositoryInterface $customerRepository,
        Registry $registry,
        CompanyRepositoryInterface $companyRepository,
        CompanyManagementInterface $companyManagement,
        CustomerRegistry $customerRegistry,
        AddressRepositoryInterface $addressRepository,
        CustomerFactory $customerFactory,
        OrderCollection $orderCollection,
        OrderFactory $orderFactory
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
        $this->companyRepository = $companyRepository;
        $this->companyManagement = $companyManagement;
        $this->customerRegistry = $customerRegistry;
        $this->addressRepository = $addressRepository;
        $this->customerFactory = $customerFactory;
        $this->orderCollection = $orderCollection;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Function to delete rejected customer
     */
    public function updateTerminatedCustomerData() {
        $this->registry->register('isSecureArea', true);
        $this->updateTerminatedConfigCustomerData();
        $this->updateTerminatedConfigPaymentData();
        $this->registry->unregister('isSecureArea');
    }

    /**
     * Function to delete rejected customer
     */
    public function updateTerminatedConfigCustomerData() {
        
        $duration = $this->dataHelper->getSystemConfigValue(
            'bat_customer_termination/delete_terminate_account/account_terminate_required_duration'
        );
        if($duration != '' && $duration >= 0) {
            $collection = $this->customerCollectionFactory->create()
                    ->addAttributeToFilter('approval_status', 9)
                    ->addAttributeToFilter('customer_termination_at',array('lt' => date('Y-m-d', strtotime('-'.$duration.' year'))));
                    //->addAttributeToFilter('customer_termination_at',array('neq' => Null));

            $this->addLog('Count of Terminated Customer Data :'.count($collection));

            if ($collection->getSize()) {
                $this->addLog('==Deleting the terminated customer data process starts==');
                foreach ($collection as $customer) {
                    $this->updateCustomerInfo($customer);
                }
                $this->addLog('==Deleting the terminated customer data process ends==');
            } else {
                $this->addLog('No data to delete terminated customers');
            }
        } else {
            $this->addLog('Duration for the customer data update not given');
        }
    }

    /**
     * Function to delete rejected customer
     */
    public function updateTerminatedConfigPaymentData() {

        $duration = $this->dataHelper->getSystemConfigValue(
            'bat_customer_termination/delete_terminate_account/account_terminate_required_duration_payment'
        );
        if($duration != '' && $duration >= 0) {
            $collection = $this->customerCollectionFactory->create()
                    ->addAttributeToFilter('approval_status', 9)
                    ->addAttributeToFilter('customer_termination_at',array('lt' => date('Y-m-d', strtotime('-'.$duration.' year'))));
                    //->addAttributeToFilter('customer_termination_at',array('neq' => Null));

            $this->addLog('Count of Terminated Customer Data for payment deletion :'.count($collection));

            if ($collection->getSize()) {
                foreach ($collection as $customer) {
                    $this->updateCustomerPaymentInfo($customer);
                }
            } else {
                $this->addLog('No data to delete terminated customers');
            }
        } else {
            $this->addLog('Duration for the update payment data not given');
        }

    }

     /**
     * Function to delete customer
     *
     * @param string $customer
     * @throws Zend_Log_Exception
     */
    public function updateCustomerInfo($customer)
    {
        try {

            $this->addLog('Customer Id :'.$customer->getId());
            $customerData = $this->customerRepository->getById($customer->getId());
            $outletId = ($customerData->getCustomAttribute('outlet_id'))
                        ?$customerData->getCustomAttribute('outlet_id')->getValue():'';
            $mobilenumber = ($customerData->getCustomAttribute('mobilenumber'))
                            ?$customerData->getCustomAttribute('mobilenumber')->getValue():'';
            $businessLicenceNumber = ($customerData->getCustomAttribute('bat_business_license_number'))
                                    ?$customerData->getCustomAttribute('bat_business_license_number')->getValue():'';
            $firstname = $customerData->getFirstname();
            $secondaryEmail = ($customerData->getCustomAttribute('secondary_email'))
                            ?$customerData->getCustomAttribute('secondary_email')->getValue():'';
            $this->addLog('Outlet Id :'.$outletId);
            $this->addLog('Mobilenumber :'.$mobilenumber);
            $this->addLog('Firstname :'.$firstname);
            $this->addLog('Secondary Email :'.$secondaryEmail);
            $this->addLog('BuisnessLicenceNumber :'.$businessLicenceNumber);

            //set password to blank
            $customerSecure = $this->customerRegistry->retrieveSecureData($customerData->getId());
            $customerSecure->setRpToken(null);
            $customerSecure->setRpTokenCreatedAt(null);
            $customerSecure->setPasswordHash('-');

            $customerData->setCustomAttribute('outlet_id','deleted');
            $customerData->setCustomAttribute('outlet_pin','-');
            $customerData->setCustomAttribute('mobilenumber',NULL);
            $customerData->setFirstName('-');
            $customerData->setCustomAttribute('bat_business_license_number','0000000000');
            $customerData->setCustomAttribute('owner_birth_year','0000');
            $customerData->setCustomAttribute('secondary_email','-');
            $customerAddresses = $this->dataHelper->getCustomerDefaultShippingAddress($customerData);
            $this->addLog('Customer Address :'.$customerAddresses);
            $this->customerRepository->save($customerData);
            $addresses = $customerData->getAddresses();
            $this->deleteCustomerAddress($addresses);

            //Deleting the company associated with customer
            $company = $this->companyManagement->getByCustomerId($customerData->getId());
            if($company) {
                $companyId = $company->getId();
                $companyData = $this->companyRepository->get($companyId);
                $companyName = $companyData->getCompanyName();
                $companyData->setCompanyName('-');
                $companyData->save();
                $this->addLog('Customer associated company Id :'.$companyId);
                $this->addLog('Customer associated company Name:'.$companyName);
            }

            $this->addLog('Updated Customer Id :'.$customer->getId());
            $this->addLog('----------------------------------');
        } catch (\Exception $e) {
            $this->addLog('Account Termination CustomerDeletion Cron : '.$e->getMessage());
        }
    }

    /**
     * Function to delete customer address
     *
     * @param array $addresses
     * @throws Zend_Log_Exception
     */
    public function deleteCustomerAddress($addresses) {
        if(count($addresses) > 0) {
            foreach ($addresses as $customerAddres) {
                try{
                    $addressId = $customerAddres->getId();
                    if($addressId != '') {  
                        $this->addressRepository->deleteById($addressId);
                    }
                } catch (\Exception $e) {
                    $this->addLog('Account Customer Address deletion Error: '.$e->getMessage());
                }
            } 
        }
    }

    /**
     * Function to delete customer
     *
     * @param string $customer
     * @throws Zend_Log_Exception
     */
    public function updateCustomerPaymentInfo($customer)
    {
        try {
            $this->addLog('Customer Id :'.$customer->getId());
            $customerFactory = $this->customerFactory->create()->load($customer->getId());
            $customerDataModel = $customerFactory->getDataModel();

          $virtualBank = $customerDataModel->getCustomAttribute('virtual_bank')->getValue();
          $virtualAccountNo = $customerDataModel->getCustomAttribute('virtual_account')->getValue();

          $this->addLog('Virtual Bank :'.$virtualBank);
          $this->addLog('Virtual Accounr :'.$virtualAccountNo);

            //$customerDataModel->setCustomAttribute('virtual_bank',NULL);
            $customerDataModel->setCustomAttribute('virtual_account','-');
        
             $customerFactory->updateData($customerDataModel);
             $customerFactory->save();
            $salesOrderCollection = [];
        
            $customerId = (int)$customerFactory->getId();
            $this->addLog('Customer Data Id :'.$customerId);
            $salesOrderCollection = $this->orderCollection->create()->addAttributeToFilter('customer_id',$customerId);
            $this->addLog('Order Count :'.count($salesOrderCollection)); 
            if(count($salesOrderCollection) > 0) {
                foreach($salesOrderCollection as $order) {
                    $this->addLog('Order Id :'.$order->getId());
                    $customerOrder = $this->orderFactory->create()->loadByIncrementId($order->getIncrementId());
                    $this->deleteOrderInvoice($customerOrder);
                    $this->deleteOrderShipments($customerOrder);
                    $this->deleteCreditMemoOrders($customerOrder);
                    $this->deleteOrder($order);
                }
            }
           
            $this->addLog('----------------------------------');
        } catch (\Exception $e) {
            $this->addLog('Account Rejected CustomerDeletion Cron : '.$e->getMessage());
        }
    }

    /**
     * Function for delete invoice
     */
    public function deleteOrderInvoice($customerOrder) {
        $invoices = $customerOrder->getInvoiceCollection();
        $this->addLog('Invoices order count'.count($invoices));
        foreach ($invoices as $invoice) {
            $this->addLog('Invoice Id: '.$invoice->getId());
            $invoice->delete();
        } 
    }

    /**
     * Function for delete shipment
     */
    public function deleteOrderShipments($customerOrder) {
        $shipments = $customerOrder->getShipmentsCollection();
        $this->addLog('Shipments order count'.count($shipments));
        foreach ($shipments as $shipment) {
            $this->addLog('Shipment Id: '.$shipment->getId());
            $shipment->delete();
        }
    }

     /**
     * Function for delete creditmemo
     */
    public function deleteCreditMemoOrders($customerOrder) {
        $creditmemos = $customerOrder->getCreditmemosCollection();
        $this->addLog('Creditmemos order count'.count($creditmemos));
        foreach ($creditmemos as $creditmemo) {
            $this->addLog('Creditmemos Id: '.$creditmemo->getId());
            $creditmemo->delete();
        }
    }

    /**
     * Function for delete order
     */
    public function deleteOrder($order) {
        $order->delete();
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
            'bat_customer_termination/delete_terminate_account/log_enabled'
        );
        if($logEnable) {
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/terminated_customer_cron.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($message);
        }
    }
}