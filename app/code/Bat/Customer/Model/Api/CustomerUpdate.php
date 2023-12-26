<?php
namespace Bat\Customer\Model\Api;

use Bat\Customer\Api\CustomerUpdateInterface;

use Bat\Customer\Model\EdaCustomersFactory;
use Bat\Customer\Model\ResourceModel\EdaCustomersResource;
use Bat\Customer\Model\SendCustomerDetails;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Bat\Customer\Helper\Data as CustomerHelper;

/**
 * @class CustomerUpdate
 * Update Customer
 */
class CustomerUpdate implements CustomerUpdateInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerHelper
     */
    private CustomerHelper $customerHelper;

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
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param CustomerHelper $customerHelper
     * @param EdaCustomersFactory $edaCustomersFactory
     * @param EdaCustomersResource $edaCustomersResource
     * @param SendCustomerDetails $sendCustomerDetails
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        CustomerHelper $customerHelper,
        EdaCustomersFactory $edaCustomersFactory,
        EdaCustomersResource $edaCustomersResource,
        SendCustomerDetails $sendCustomerDetails,
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->customerHelper = $customerHelper;
        $this->edaCustomersFactory = $edaCustomersFactory;
        $this->edaCustomersResource = $edaCustomersResource;
        $this->sendCustomerDetails = $sendCustomerDetails;
    }

    /**
     * Update Customer
     *
     * @param string $batchId
     * @param string $createdAt
     * @param string $countryCode
     * @param string $companyCode
     * @param string $outletCode
     * @param string $sapOutletCode
     * @return array
     */
    public function updateCustomer($batchId, $createdAt, $countryCode, $companyCode, $outletCode, $sapOutletCode)
    {
        $result = [];
        try {
            $data = [
                'outlet_code' => $outletCode,
                'sap_outlet_code' => $sapOutletCode,
                'created_at' =>$createdAt,
                'batch_id' => $batchId,
                'company_code' => $companyCode,
                'country_code' => $countryCode
            ];
            $this->addLog("====================================================");
            $this->addLog("Request : ");
            $this->addLog(json_encode($data));
            $this->addLog("Response : ");
            $this->validateInput($batchId, $createdAt, $countryCode, $companyCode, $outletCode, $sapOutletCode);
            $customer = $this->customerHelper->isOutletIdValidCustomer($outletCode);
            $customerId = $customer->getId();
            if ($customer) {
                $customer = $this->customerRepository->getById($customerId);
                $customer->setCustomAttribute('sap_outlet_code', $sapOutletCode);
                $customer->setCustomAttribute('bat_batch_id', $batchId);
                $customer->setCustomAttribute('bat_created_at', $createdAt);
                $customer->setCustomAttribute('bat_company_code', $companyCode);
                $customer->setCustomAttribute('bat_country_code', $countryCode);
                $customer->setCustomAttribute('customer_sap_confirmation_status', 1);
                $this->customerRepository->save($customer);
                $edaCustomerOms = $this->sendCustomerDetails->getEdaCustomerForUpdate($customerId, 'OMS');
                if (!empty($edaCustomerOms->getData())) {
                    $edaCustomerOms->setUpdateType('new');
                    $edaCustomerOms->setFailureAttempts(0);
                    $edaCustomerOms->setCustomerSent(0);
                    $edaCustomerOms->setChannel('OMS');
                    $this->edaCustomersResource->save($edaCustomerOms);
                } else {
                    $edaCustomerOms = $this->edaCustomersFactory->create();
                    $edaCustomerOms->setData(
                        ['customer_id' => $customerId, 'update_type' => 'new', 'channel' => 'OMS']
                    );
                    $this->edaCustomersResource->save($edaCustomerOms);
                }
                $result[] = ['success' => true, 'message'=>'Customer updated successfully'];
            } else {
                throw new LocalizedException(__('Customer not found'));
            }
        } catch (\Exception $e) {
            $result[] = ['success' => false, 'message'=>$e->getMessage()];
        }
        $this->addLog(json_encode($result));
        return $result;
    }

    /**
     * Validate customer update input
     *
     * @param string $batchId
     * @param string $createdAt
     * @param string $countryCode
     * @param string $companyCode
     * @param string $outletCode
     * @param string $sapOutletCode
     * @throws LocalizedException
     */
    public function validateInput($batchId, $createdAt, $countryCode, $companyCode, $outletCode, $sapOutletCode)
    {
        if (trim($outletCode) == '') {
            throw new LocalizedException(__('outlet_code is required to update customer'));
        }
        if (trim($sapOutletCode) == '') {
            throw new LocalizedException(__('sap_outlet_code is required to update customer'));
        }
        if (trim($createdAt) == '') {
            throw new LocalizedException(__('created_at is required to update customer'));
        }
        if (trim($companyCode) == '') {
            throw new LocalizedException(__('company_code is required to update customer'));
        }
        if (trim($countryCode) == '') {
            throw new LocalizedException(__('country_code is required to update customer'));
        }
        if (trim($batchId) == '') {
            throw new LocalizedException(__('batch_id is required to update customer'));
        }
    }

    /**
     * Customer confirmation Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addlog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaCustomerConfirmation.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
