<?php

namespace Bat\Customer\Cron;

use Bat\Customer\Helper\Data;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

/**
 * @class TerminateCustomer
 * Cron to terminate customer
 */
class TerminateCustomer
{
    /**
     * @var Data
     */
    private Data $dataHelper;

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
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    private TimezoneInterface $timezoneInterface;

    /**
     * @param Data $dataHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        Data $dataHelper,
        CustomerRepositoryInterface $customerRepository,
        CompanyRepositoryInterface $companyRepository,
        CompanyManagementInterface $companyManagement,
        LoggerInterface $logger,
        TimezoneInterface $timezoneInterface
    ) {
        $this->dataHelper = $dataHelper;
        $this->customerRepository = $customerRepository;
        $this->companyRepository = $companyRepository;
        $this->companyManagement = $companyManagement;
        $this->logger = $logger;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Terminate customer
     */
    public function execute()
    {
        $this->logger->info('terminate customer');
        $customerCollection = $this->dataHelper->getCustomerCollectionForTermination();
        if ($customerCollection->getSize()) {
            $terminationDuration = "+".$this->dataHelper->getSystemConfigValue(
                'bat_customer_termination/general/account_termination_required_duration'
            )." Months";
            $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
            foreach ($customerCollection as $customer) {
                try {
                    $deactivatedDate = $customer->getDeactivatedAt();
                    $terminationDate = $this->timezoneInterface->date($deactivatedDate)
                        ->modify($terminationDuration)->format('Y-m-d');
                    if ($currentDate >= $terminationDate) {
                        $customerTerminated = $this->customerRepository->getById($customer->getId());
                        $customerTerminated->setCustomAttribute('approval_status', '9');
                        $customerTerminated->setCustomAttribute('customer_termination_at', $currentDate);
                        $this->customerRepository->save($customerTerminated);
                    }
                } catch (\Exception $e) {
                    $this->logger->info('Account termination Cron : '.$e->getMessage());
                }
            }
        } else {
            $this->logger->info('No customers to Terminate');
        }
    }
}
