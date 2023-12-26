<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\VirtualBank\Controller\Adminhtml\VirtualAccount;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Bat\VirtualBank\Model\ResourceModel\AccountResource;
use Bat\VirtualBank\Model\ResourceModel\AccountResource\CollectionFactory as AccountCollectionFactory;
use Bat\VirtualBank\Model\AccountModelFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Bat\VirtualBank\Helper\Data as VbaHelper;
use Magento\Framework\Exception\LocalizedException;

/**
 * @class ImportPost
 * Import class for Virtual Account Numbers
 */
class ImportPost extends Action
{
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;
    /**
     * @var Filesystem
     */
    private Filesystem $_filesystem;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $_storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $_scopeConfig;
    /**
     * @var ConfigInterface
     */
    private ConfigInterface $_configResource;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $_resource;
    /**
     * @var Config
     */
    private Config $mediaConfig;
    /**
     * @var Csv
     */
    private Csv $csvProcessor;
    /**
     * @var AccountResource
     */
    private AccountResource $accountResource;
    /**
     * @var BankCollectionFactory
     */
    private BankCollectionFactory $bankCollectionFactory;
    /**
     * @var AccountCollectionFactory
     */
    private AccountCollectionFactory $accountCollectionFactory;
    /**
     * @var AccountModelFactory
     */
    private AccountModelFactory $accountModelFactory;
    /**
     * @var UploaderFactory
     */
    private UploaderFactory $fileUploaderFactory;
    /**
     * @var VbaHelper
     */
    private VbaHelper $vbaHelper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resource
     * @param ConfigInterface $configResource
     * @param Config $mediaConfig
     * @param Csv $csvProcessor
     * @param BankCollectionFactory $bankCollectionFactory
     * @param AccountResource $accountResource
     * @param AccountModelFactory $accountModel
     * @param AccountCollectionFactory $accountCollectionFactory
     * @param UploaderFactory $fileUploaderFactory
     * @param VbaHelper $vbaHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        ConfigInterface $configResource,
        Config $mediaConfig,
        Csv $csvProcessor,
        BankCollectionFactory $bankCollectionFactory,
        AccountResource $accountResource,
        AccountModelFactory $accountModel,
        AccountCollectionFactory $accountCollectionFactory,
        UploaderFactory $fileUploaderFactory,
        VbaHelper $vbaHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_filesystem       = $filesystem;
        $this->_storeManager     = $storeManager;
        $this->_scopeConfig      = $scopeConfig;
        $this->_configResource   = $configResource;
        $this->_resource         = $resource;
        $this->mediaConfig       = $mediaConfig;
        $this->csvProcessor = $csvProcessor;
        $this->bankCollectionFactory = $bankCollectionFactory;
        $this->accountResource = $accountResource;
        $this->accountModelFactory = $accountModel;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->vbaHelper = $vbaHelper;
    }

    /**
     * Import action for VBA
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParams();
        $filePath = '';
        try {
            $uploader = $this->fileUploaderFactory->create(['fileId' => 'data_import_file']);
            if ($uploader) {
                $tmpDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::TMP);
                $savePath     = $tmpDirectory->getAbsolutePath('vba/import');
                $uploader->setAllowRenameFiles(true);
                $result       = $uploader->save($savePath);
                $filePath = $tmpDirectory->getAbsolutePath('vba/import/' . $result['file']);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("Can't import data<br/> %1", $e->getMessage()));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
        $importData = $this->csvProcessor->getData($filePath);
        $heading = $importData[0];
        unset($importData[0]);
        if (!empty($importData)) {
            $accountNoIndex = array_search("vba_no", $heading);
            $bankCodeIndex = array_search("bank_code", $heading);
            if ($bankCodeIndex !== false && $accountNoIndex !== false) {
                $bankCodes = $this->getBankCodes($importData, $bankCodeIndex);
                $errors = [];
                $importedSuccessfully = 0;
                $rowCounter = 1;
                $virtualAccountNumbers = $this->getAccountNumbersCollection($bankCodes);
                $accountNumbersProcessed = [];
                foreach ($importData as $data) {
                    try {
                        $virtualBank = $data[$bankCodeIndex];
                        $rowCounter++;
                        $this->validateRow(
                            $virtualBank,
                            $virtualAccountNumbers,
                            $accountNoIndex,
                            $data,
                            $accountNumbersProcessed,
                            $bankCodes
                        );
                        $accountNumbersProcessed[] = $data[$accountNoIndex];
                        $accountData = $this->accountModelFactory->create();
                        $accountData->setData(
                            [
                                'vba_no' => $data[$accountNoIndex],
                                'bank_code'=>$virtualBank
                            ]
                        );
                        $this->accountResource->save($accountData);
                        $importedSuccessfully++;
                    } catch (\Exception $e) {
                        $errors[] = '<br/>Record'.'-'.$rowCounter.' :'.$e->getMessage();
                    }
                }
                if ($importedSuccessfully) {
                    $this->messageManager->addSuccessMessage(
                        __("Successfully Imported %1 Records", $importedSuccessfully)
                    );
                }
                if (!empty($errors)) {
                    $this->messageManager->addError(
                        __('Error Report:  %1', implode(',', $errors))
                    );
                }

                return $resultRedirect->setPath('*/*/new');
            } else {
                $this->messageManager->addErrorMessage(
                    __("Please provide column vba_no and bank_code to import virtual accounts")
                );
                return $resultRedirect->setPath('*/*/new');
            }
        } else {
            $this->messageManager->addErrorMessage(
                __("Please Add Account Numbers to Import")
            );
            return $resultRedirect->setPath('*/*/new');
        }
    }

    /**
     * Return virtual account collection
     *
     * @param String $bank
     * @return array
     */
    public function getVirtualAccountCollectionOnBank($bank)
    {
        $virtualAccountNumbers = [];
        $accountCollection = $this->accountCollectionFactory->create()
            ->addFieldToSelect('vba_no')
            ->addFieldToFilter('bank_code', ['eq'=>$bank]);
        foreach ($accountCollection as $account) {
            $virtualAccountNumbers[] = $account->getVbaNo();
        }
        return $virtualAccountNumbers;
    }

    /**
     * Validate VBA Import
     *
     * @param string $virtualBank
     * @param array $virtualAccountNumbers
     * @param int $accountNoIndex
     * @param array $data
     * @param array $accountNumbersProcessed
     * @param array $bankCodes
     * @throws LocalizedException
     */
    public function validateRow(
        $virtualBank,
        $virtualAccountNumbers,
        $accountNoIndex,
        $data,
        $accountNumbersProcessed,
        $bankCodes
    ) {
        if (!isset($bankCodes[$virtualBank])) {
            throw new LocalizedException(__('Update Bank Code'));
        }
        if (isset($bankCodes[$virtualBank])) {
            if (!$bankCodes[$virtualBank]['success']) {
                throw new LocalizedException(__($bankCodes[$virtualBank]['message']));
            }
        }
        if (!$data[$accountNoIndex]) {
            throw new LocalizedException(__('Update Virtual Account No'));
        } else {
            if(!is_numeric($data[$accountNoIndex]) || $data[$accountNoIndex] <= 0 ||
                $data[$accountNoIndex] != round($data[$accountNoIndex])){
                throw new LocalizedException(__('Invalid Virtual Account No'));
            }
        }
        if (in_array($data[$accountNoIndex], $virtualAccountNumbers[$virtualBank])) {
            throw new LocalizedException(__('Duplicate Virtual Account No'));
        }
        $isAssignedToCustomer = $this->vbaHelper->checkVirtualAccountNoAssigned($data[$accountNoIndex]);
        if ($isAssignedToCustomer) {
            throw new LocalizedException(__('Virtual Account No Already assigned to a user'));
        }
        if (in_array($data[$accountNoIndex], $accountNumbersProcessed)) {
            throw new LocalizedException(__('Duplicate Virtual Account No in Import'));
        }
    }

    /**
     * Return Bank Status
     *
     * @param mixed $importData
     * @param int $bankCodeIndex
     * @return array
     */
    public function getBankCodes($importData, $bankCodeIndex)
    {
        $bankCodes = [];
        foreach ($importData as $data) {
            $bankCode = $data[$bankCodeIndex];
            if ($bankCode != '' && !isset($bankCodes[$bankCode])) {
                $status = $this->vbaHelper->getVirtualBankStatus($bankCode);
                $bankCodes[$bankCode] = $status;
            }
        }
        return $bankCodes;
    }

    /**
     * Return Account Numbers based on bank code
     *
     * @param array $bankCodes
     * @return array
     */
    public function getAccountNumbersCollection($bankCodes)
    {
        $bankAccountNumbers = [];
        foreach ($bankCodes as $bankCode => $value) {
            if ($value['success']) {
                $bankAccountNumbers[$bankCode] = $this->getVirtualAccountCollectionOnBank($bankCode);
            }
        }
        return $bankAccountNumbers;
    }
}
