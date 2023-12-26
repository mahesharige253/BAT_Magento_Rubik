<?php

namespace Bat\VirtualBank\Helper;

use Bat\VirtualBank\Model\ResourceModel\AccountResource\CollectionFactory as AccountCollectionFactory;
use Bat\VirtualBank\Model\ResourceModel\AccountResource;
use Bat\VirtualBank\Model\AccountModel;
use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * @class Data
 * Helper class for VBA
 */
class Data extends AbstractHelper
{
    protected const XML_PATH_EMAIL_RECIPIENT = 'vba_config/general/vba_notify_to_email';
    protected const XML_PATH_EMAIL_RECIPIENT_NAME = 'vba_config/general/vba_notify_to_name';
    protected const XML_PATH_SENDER_EMAIL = 'trans_email/ident_general/email';
    protected const XML_PATH_SENDER_NAME = 'trans_email/ident_general/name';
    protected const XML_PATH_EMAIL_TEMPLATE = 'vba_config/general/vba_accounts_notify_admin';

    /**
     * @var AccountResource
     */
    private AccountResource $accountResource;
    /**
     * @var AccountCollectionFactory
     */
    private AccountCollectionFactory $accountCollectionFactory;
    /**
     * @var AccountModel
     */
    private AccountModel $accountModel;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var TransportBuilder
     */
    private TransportBuilder $_transportBuilder;
    /**
     * @var StateInterface
     */
    private StateInterface $inlineTranslation;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var Escaper
     */
    private Escaper $_escaper;
    /**
     * @var BankCollectionFactory
     */
    private BankCollectionFactory $bankCollectionFactory;

    /**
     * @param Context $context
     * @param AccountCollectionFactory $accountCollectionFactory
     * @param AccountModel $accountModel
     * @param AccountResource $accountResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Escaper $escaper
     * @param BankCollectionFactory $bankCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        AccountCollectionFactory $accountCollectionFactory,
        AccountModel $accountModel,
        AccountResource $accountResource,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Escaper $escaper,
        BankCollectionFactory $bankCollectionFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->accountModel = $accountModel;
        $this->accountResource = $accountResource;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->_escaper = $escaper;
        $this->bankCollectionFactory = $bankCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Check virtual numbers availability
     *
     * @param String $bankCode
     * @return array
     * check available virtual accounts for a specific bank
     */
    public function isVirtualAccountNumbersAvailable($bankCode)
    {
        $bankDetails = $this->getVirtualBankStatus($bankCode);
        if (!$bankDetails['success']) {
            return ['status'=>'0','msg'=>$bankDetails['message']];
        }
        $collection = $this->accountCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('bank_code', ['eq'=>$bankCode])
            ->addFieldToFilter('vba_assigned_status', ['eq'=>0])
            ->setOrder('vba_assigned_status', 'asc');
        $accountNo = '';
        $accountId = '';
        if ($collection->count()) {
            $account = $collection->getFirstItem();
            $accountNo = $account->getVbaNo();
            $accountId = $account->getVbaId();
        }
        if ($accountNo) {
            return ['acc_no'=>$accountNo,'acc_id'=>$accountId,'status'=>'1','msg'=>'success'];
        } else {
            return [
                'status'=>'0',
                'msg'=>__("No Virtual Accounts Available for the selected bank")
            ];
        }
    }

    /**
     * Delete assigned account no
     *
     * @param Int $accountId
     * @throws \Exception
     * Delete Virtual Account No
     */
    public function deleteAccountNo($accountId)
    {
        $currentAccount = $this->accountModel->load($accountId);
        $currentAccount->delete();
    }

    /**
     * Check If Account Number Assigned
     *
     * @param String $accountNo
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * Checks if an virtual account no is already assigned to a user
     */
    public function checkVirtualAccountNoAssigned($accountNo)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'virtual_account',
            $accountNo,
            'eq'
        )->create();
        $customerData = $this->customerRepository->getList($searchCriteria);
        if ($customerData->getTotalCount()) {
            return true;
        }
        return false;
    }

    /**
     * Send Email to admin
     *
     * @param Array $notifyBanks
     * Notify admin on virtual accounts availabiltity
     */
    public function sendEmail($notifyBanks)
    {
        $this->inlineTranslation->suspend();
        try {
            $storeScope = ScopeInterface::SCOPE_STORE;
            $receiverName = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_NAME, $storeScope);
            $receiverEmail = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope);
            $senderName = $this->scopeConfig->getValue(self::XML_PATH_SENDER_NAME, $storeScope);
            $senderEmail = $this->scopeConfig->getValue(self::XML_PATH_SENDER_EMAIL, $storeScope);

            $sender = [
                'name' => $senderName,
                'email' => $senderEmail,
            ];
            $emailTemplateVariables  = [
                'name' => $receiverName,
                'banks' => $notifyBanks
            ];
            $templateId = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE, $storeScope);
            if ($templateId == '') {
                $templateId = 'vba_config_general_vba_accounts_notify_admin';
            }
            $transport =
                $this->_transportBuilder
                    ->setTemplateIdentifier($templateId)
                    ->setTemplateOptions(
                        ['area' => Area::AREA_FRONTEND,
                            'store' => Store::DEFAULT_STORE_ID,]
                    )
                    ->setTemplateVars($emailTemplateVariables)
                    ->setFrom($sender)
                    ->addTo($receiverEmail)
                    ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            return true;
        } catch (\Exception $e) {
            $this->logger->critical(
                'Virtual Account Availability Notification Email not sent'.$e->getMessage()
            );
            return false;
        }
    }

    /**
     * Return Bank Status
     *
     * @param string $bankCode
     * @return array
     */
    public function getVirtualBankStatus($bankCode)
    {
        $result = ['success' => false, 'message'=>'Bank Not Available'];
        $collection = $this->bankCollectionFactory->create();
        $collection->addFieldToSelect('*')
            ->addFieldToFilter('bank_code', ['eq'=>$bankCode]);
        if ($collection) {
            foreach ($collection as $bank) {
                if ($bank->getBankStatus()) {
                    $result = ['success' => true, 'message'=>'Bank Enabled'];
                } else {
                    $result = ['success' => false, 'message'=>'Bank Disabled'];
                }
            }
        }
        return $result;
    }

    /**
     * Return Bank Name by bank code
     *
     * @param string $bankCode
     * @return array
     */
    public function getVirtualBankName($bankCode)
    {
        $bankName = '';
        $collection = $this->bankCollectionFactory->create();
        $collection->addFieldToSelect('*')
            ->addFieldToFilter('bank_code', ['eq'=>$bankCode]);
        if ($collection) {
            foreach ($collection as $bank) {
                if ($bank->getBankName()) {
                    $bankName = $bank->getBankName();
                }
            }
        }
        return $bankName;
    }

    /**
     * Get Customer current bank by customer id
     *
     * @param string $customerId
     *
     * @return array
     */
    public function getCurrentBank($customerId)
    {
        $currentBank = [];
            $customer = $this->customerRepository->getById($customerId);
        if ($customer->getCustomAttribute('virtual_bank')) {
            $currentBank['code'] = $customer->getCustomAttribute('virtual_bank')->getValue();
            $currentBank['label'] = $this->getVirtualBankName($currentBank['code']);
        }

        return $currentBank;
    }


    /**
     * Return enabled banks and available accounts count
     *
     * @return array
     */
    public function getEnabledBanks()
    {
        $enabledBanks = [];
        $banks = $this->bankCollectionFactory->create()->addFieldToFilter('bank_status', ['eq' => 1]);
        foreach ($banks as $bank) {
            $bankDetails['bank_name'] = $bank->getBankName();
            $bankDetails['available_accounts'] = $this->getAvailableVirtualAccountsByBankCode($bank->getBankCode());
            $enabledBanks[] = $bankDetails;
        }
        return $enabledBanks;
    }

    /**
     * Return Available virtual accounts count
     *
     * @param string $bankCode
     * @return int
     */
    public function getAvailableVirtualAccountsByBankCode($bankCode)
    {
        $virtualAccounts = $this->accountCollectionFactory->create()
            ->addFieldToFilter('bank_code', ['eq' => $bankCode])
            ->addFieldToFilter('vba_assigned_status', ['eq' => 0]);
        return $virtualAccounts->getSize();
    }
}
