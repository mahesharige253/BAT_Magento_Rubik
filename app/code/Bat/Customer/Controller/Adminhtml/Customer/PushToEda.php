<?php

namespace Bat\Customer\Controller\Adminhtml\Customer;

use Bat\Customer\Model\SendCustomerDetails;
use Bat\Integration\Helper\Data as IntegrationHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * @class PushToEda
 * Push Customer details to EDA
 */
class PushToEda extends Action
{
    private const CUSTOMER_LOG_ENABLED_CONFIG_PATH = "bat_integrations/bat_customer/eda_customer_log";
    private const MAX_FAILURES_ALLOWED_CONFIG_PATH = "bat_integrations/bat_customer/eda_customer_max_failures_allowed";
    private const EDA_CUSTOMER_UPDATE_ENDPOINT_PATH = "bat_integrations/bat_customer/eda_customer_endpoint";

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var SendCustomerDetails
     */
    private SendCustomerDetails $sendCustomerDetails;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var IntegrationHelper
     */
    private IntegrationHelper $integrationHelper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ManagerInterface $messageManager
     * @param Data $data
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param SendCustomerDetails $sendCustomerDetails
     * @param CompanyManagementInterface $companyManagement
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        Data $data,
        CustomerRepositoryInterface $customerRepositoryInterface,
        SendCustomerDetails $sendCustomerDetails,
        CompanyManagementInterface $companyManagement,
        IntegrationHelper $integrationHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->data = $data;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->sendCustomerDetails = $sendCustomerDetails;
        $this->companyManagement = $companyManagement;
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * Push Customer to EDA
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $customerId = $this->getRequest()->getParam('customer_id');
        $channel = $this->getRequest()->getParam('channel');
        $maxFailuresAllowed = $this->data->getSystemConfigValue(self::MAX_FAILURES_ALLOWED_CONFIG_PATH);
        $apiEndPoint = $this->data->getSystemConfigValue(self::EDA_CUSTOMER_UPDATE_ENDPOINT_PATH);
        $logEnabled = $this->data->getSystemConfigValue(self::CUSTOMER_LOG_ENABLED_CONFIG_PATH);
        try {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $company = $this->companyManagement->getByCustomerId($customerId);
            $approvalStatus = $customer->getCustomAttribute('approval_status');
            $sapOutletCode = $customer->getCustomAttribute('sap_outlet_code');
            $approvalStatus = ($approvalStatus) ? $approvalStatus->getValue() : '';
            $sapOutletCode = ($sapOutletCode) ? $sapOutletCode->getValue() : '';
            if ($channel == 'SWIFTPLUS') {
                if ($approvalStatus == 5 && $sapOutletCode == '') {
                    if ($logEnabled) {
                        $this->data->logEdaCustomerUpdateRequest(
                            '==============================================',
                        );
                        $this->data->logEdaCustomerUpdateRequest('Request : CustomerId - '.$customerId);
                    }
                    $customerData = json_encode($this->sendCustomerDetails->formatCustomerData(
                        $customer,
                        $company,
                        'update',
                        $channel
                    ));
                    if ($logEnabled) {
                        $this->data->logEdaCustomerUpdateRequest($customerData);
                    }
                    $status = $this->integrationHelper->postDataToEda($customerData, $apiEndPoint);
                    $statusDecoded = json_decode($status, true);
                    $result = false;
                    if (isset($statusDecoded['success']) && $statusDecoded['success']) {
                        $result = true;
                    }
                    if ($logEnabled) {
                        $statusLog = ($result) ? 'success' : 'failure';
                        $this->data->logEdaCustomerUpdateRequest('Response : '.$statusLog);
                        $this->data->logEdaCustomerUpdateRequest($status);
                    }
                    if ($result) {
                        $this->messageManager->addSuccessMessage(__("Customer pushed successfully"));
                    } else {
                        $this->messageManager->addErrorMessage(__("Customer Push Failed"));
                    }
                }
            }
        } catch (\Throwable $e) {
            if ($logEnabled) {
                $this->data->logEdaCustomerUpdateRequest(
                    'Update to EDA Exception - customer Id : '.$customerId.' - '.$e->getMessage()
                );
                $this->data->logEdaCustomerUpdateRequest(
                    'Trace : Line No '.$e->getLine().', '.$e->getTraceAsString()
                );
            }
            $this->messageManager->addErrorMessage(__("Customer Push Failed : ".$e->getMessage()));
        }
        $resultRedirect->setPath('customer/index/edit/', ['id' => $customerId]);
        return $resultRedirect;
    }
}
