<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\CustomerImport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Exception\LocalizedException;
use Bat\CustomerImport\Model\ImportCustomer;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @class ImportPost
 * Import class for Customers
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
     * @var UploaderFactory
     */
    private UploaderFactory $fileUploaderFactory;

    /**
     * @var ImportCustomer
     */
    protected ImportCustomer $importCustomer;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Filesystem $filesystem
     * @param UploaderFactory $fileUploaderFactory
     * @param ImportCustomer $importCustomer
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Filesystem $filesystem,
        UploaderFactory $fileUploaderFactory,
        ImportCustomer $importCustomer
    ) {
        parent::__construct($context);
        $this->resultPageFactory   = $resultPageFactory;
        $this->_filesystem         = $filesystem;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->importCustomer      = $importCustomer;
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
                $savePath     = $tmpDirectory->getAbsolutePath('customer/import');
                $uploader->setAllowRenameFiles(true);
                $result       = $uploader->save($savePath);
                $filePath = $tmpDirectory->getAbsolutePath('customer/import/' . $result['file']);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("Can't import data<br/> %1", $e->getMessage()));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }

        $responses = $this->importCustomer->customers($filePath);
        foreach ($responses as $response) {
            if ($response['message']) {
                $this->messageManager->addErrorMessage($response['message']);
            } else {
                $this->messageManager->addSuccess($response['message']);
            }
        }
        return $resultRedirect->setPath('*/*/customerImport');
    }
}
