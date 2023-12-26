<?php

namespace Bat\CustomerConsentForm\Controller\Adminhtml\Create;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Io\File;

class Save extends \Magento\Backend\App\Action
{

    /**
     * @var boolean
     */
    protected $resultPageFactory = false;

    /**
     * @var  \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploader;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_mediaDirectory;

    /**
     * @var ConsentFormFactory
     */
    protected $consentformFactory;

    /**
     * @var ResultFactory
     */
    protected $_resultFactory;

    /**
     * @var File
     */
    protected $fileIo;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context                      $context
     * @param \Bat\CustomerConsentForm\Model\ConsentFormFactory        $consentformFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory         $uploader
     * @param \Magento\Framework\Filesystem                            $filesystem
     * @param File                                                     $fileIo
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bat\CustomerConsentForm\Model\ConsentFormFactory $consentformFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Framework\Filesystem $filesystem,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->consentformFactory = $consentformFactory;
        $this->uploader = $uploader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->fileIo = $fileIo;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        if(isset($data['consent_type'])){
        $data['consent_type'] = implode(',', $data['consent_type']);
        }
        else{
            $data['consent_type'] = '';
        }
        $consentId = isset($data['id']) ? $data['id'] : '';
        if (!$data) {
            $this->_redirect('customerconsentform/create/index');
        }
        try {
            $rowData = $this->consentformFactory->create()->load($consentId);
            if (!$rowData->getId() && $consentId) {
                $this->messageManager->addError(__('row data no longer exist.'));
                $this->_redirect('customerconsentform/create/index');
            }
            $rowData->setData($data);
            $rowData->save();
            $this->messageManager->addSuccess(__('Consent form has been Succesfully Saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('customerconsentform/create/index');
    }
}
