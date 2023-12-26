<?php

namespace Bat\Customer\Cron;

use Bat\Customer\Helper\Data;
use Bat\Customer\Model\EdaCustomersFactory;
use Bat\Customer\Model\ResourceModel\EdaCustomersResource;
use Bat\Customer\Model\SendCustomerDetails;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\Integration\Helper\Data as IntegrationHelper;
use Magento\Framework\Exception\LocalizedException;

/**
 * @class CreateCustomerEda
 * Cron to create/update orders in EDA
 */
class CreateCustomerEda
{
    private const CUSTOMER_LOG_ENABLED_CONFIG_PATH = "bat_integrations/bat_customer/eda_customer_log";
    private const MAX_FAILURES_ALLOWED_CONFIG_PATH = "bat_integrations/bat_customer/eda_customer_max_failures_allowed";
    private const EDA_CUSTOMER_UPDATE_ENDPOINT_PATH = "bat_integrations/bat_customer/eda_customer_endpoint";

    /**
     * @var int
     */
    private $maxFailuresAllowed;

    /**
     * @var string
     */
    private $apiEndPoint;

    /**
     * @var boolean
     */
    private $logEnabled;

    /**
     * @var SendCustomerDetails
     */
    private SendCustomerDetails $sendCustomerDetails;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var EdaCustomersFactory
     */
    private EdaCustomersFactory $edaCustomersFactory;

    /**
     * @var EdaCustomersResource
     */
    private EdaCustomersResource $edaCustomersResource;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CompanyRepositoryInterface
     */
    private CompanyRepositoryInterface $companyRepository;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var IntegrationHelper
     */
    private IntegrationHelper $integrationHelper;

    /**
     * @param SendCustomerDetails $sendCustomerDetails
     * @param Data $dataHelper
     * @param EdaCustomersFactory $edaCustomersFactory
     * @param EdaCustomersResource $edaCustomersResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(
        SendCustomerDetails $sendCustomerDetails,
        Data $dataHelper,
        EdaCustomersFactory $edaCustomersFactory,
        EdaCustomersResource $edaCustomersResource,
        CustomerRepositoryInterface $customerRepository,
        CompanyRepositoryInterface $companyRepository,
        CompanyManagementInterface $companyManagement,
        IntegrationHelper $integrationHelper
    ) {
        $this->sendCustomerDetails = $sendCustomerDetails;
        $this->dataHelper = $dataHelper;
        $this->edaCustomersFactory = $edaCustomersFactory;
        $this->edaCustomersResource = $edaCustomersResource;
        $this->customerRepository = $customerRepository;
        $this->companyRepository = $companyRepository;
        $this->companyManagement = $companyManagement;
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * Create customers in EDA
     */
    public function execute()
    {
        $this->logEnabled = $this->dataHelper->getSystemConfigValue(self::CUSTOMER_LOG_ENABLED_CONFIG_PATH);
        try {
            $this->maxFailuresAllowed = $this->dataHelper->getSystemConfigValue(
                self::MAX_FAILURES_ALLOWED_CONFIG_PATH
            );
            $edaCustomerCollection = $this->sendCustomerDetails->getEdaCustomerCollection($this->maxFailuresAllowed);
            if ($edaCustomerCollection->count()) {
                $this->apiEndPoint = $this->dataHelper->getSystemConfigValue(
                    self::EDA_CUSTOMER_UPDATE_ENDPOINT_PATH
                );
                foreach ($edaCustomerCollection as $edaCustomer) {
                    $result = $this->processCustomer($edaCustomer);
                    if ($result) {
                        $edaCustomer->setCustomerSent(1);
                    } else {
                        $failureAttempts = $edaCustomer['failure_attempts'] + 1;
                        $edaCustomer->setFailureAttempts($failureAttempts);
                    }
                    $this->edaCustomersResource->save($edaCustomer);
                }
            } else {
                if ($this->logEnabled) {
                    $this->dataHelper->logEdaCustomerUpdateRequest(
                        '=============================================='
                    );
                    $this->dataHelper->logEdaCustomerUpdateRequest('No Customers to update');
                }
            }
        } catch (\Exception $e) {
            if ($this->logEnabled) {
                $this->dataHelper->logEdaCustomerUpdateRequest($e->getMessage());
            }
        }
    }

    /**
     * Process Customer data to EDA
     *
     * @param mixed $edaCustomer
     * @return bool|false[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processCustomer($edaCustomer)
    {
        $result = false;
        try {
            $customerId = $edaCustomer['customer_id'];
            $updateType = $edaCustomer['update_type'];
            $channel = $edaCustomer['channel'];
            $customer = $this->customerRepository->getById($customerId);
            if ($this->logEnabled) {
                $this->dataHelper->logEdaCustomerUpdateRequest(
                    '==============================================',
                );
                $this->dataHelper->logEdaCustomerUpdateRequest('Request : CustomerId - '.$customerId);
            }
            $company = $this->companyManagement->getByCustomerId($customerId);
            if (!$company) {
                throw new LocalizedException(__('Company Not found'));
            }
            $customerData = json_encode($this->sendCustomerDetails->formatCustomerData(
                $customer,
                $company,
                $updateType,
                $channel
            ));
            if ($this->logEnabled) {
                $this->dataHelper->logEdaCustomerUpdateRequest($customerData);
            }
            $status = $this->integrationHelper->postDataToEda($customerData, $this->apiEndPoint);
            $statusDecoded = json_decode($status, true);
            if (isset($statusDecoded['success']) && $statusDecoded['success']) {
                $result = true;
            }
            if ($this->logEnabled) {
                $statusLog = ($result) ? 'success' : 'failure';
                $this->dataHelper->logEdaCustomerUpdateRequest('Response : '.$statusLog);
                $this->dataHelper->logEdaCustomerUpdateRequest($status);
            }
        } catch (\Throwable $e) {
            if ($this->logEnabled) {
                $this->dataHelper->logEdaCustomerUpdateRequest(
                    'Update to EDA Exception - customer Id : '.$edaCustomer['customer_id'].' - '.$e->getMessage()
                );
                $this->dataHelper->logEdaCustomerUpdateRequest(
                    'Trace : Line No '.$e->getLine().', '.$e->getTraceAsString()
                );
            }
        }
        return $result;
    }
}
