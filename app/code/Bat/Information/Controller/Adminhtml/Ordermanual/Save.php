<?php

namespace Bat\Information\Controller\Adminhtml\Ordermanual;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Io\File;

class Save extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_ordermanual';

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
     * @var InformationOrderManualFactory
     */
    protected $informationOrderManual;

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
     * @param \Bat\Information\Model\InformationOrderManualFactory     $informationOrderManual
     * @param \Magento\MediaStorage\Model\File\UploaderFactory         $uploader
     * @param \Magento\Framework\Filesystem                            $filesystem
     * @param File                                                     $fileIo
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bat\Information\Model\InformationOrderManualFactory $informationOrderManual,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Framework\Filesystem $filesystem,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationOrderManual = $informationOrderManual;
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

        $orderPdf = '';
        $ordermanualImg = '';
        $targetImg = $this->_mediaDirectory->getAbsolutePath('/Information/Image/');
        $target = $this->_mediaDirectory->getAbsolutePath('/Information/Pdf/');
        $orderPdf = $this->getRequest()->getFiles('orderpdf')['name'];
        $ordermanualImg = $this->getRequest()->getFiles('ordermanualbanner')['name'];

        if (isset($data['ordermanualbanner']) && isset($data['ordermanualbanner']['delete'])) {
            if ($data['ordermanualbanner']['delete'] == 1) {
                unset($data['ordermanualbanner']);
                $data['ordermanualbanner'] = '';
            }
        }
        if (isset($data['ordermanualbanner']) && isset($data['ordermanualbanner']['value'])) {
            $data['ordermanualbanner'] = $data['ordermanualbanner']['value'];
        }

        if (isset($data['orderpdf']) && isset($data['orderpdf']['delete'])) {
            if ($data['orderpdf']['delete'] == 1) {
                unset($data['orderpdf']);
                $data['orderpdf'] = '';
            }
        }
        if (isset($data['orderpdf']) && isset($data['orderpdf']['value'])) {
            $data['orderpdf'] = $data['orderpdf']['value'];
        }
   
        $model = $this->informationOrderManual->create();
        $err3 = 'Please upload valid type of format for OrderManual Banner Image';
        $err4 = 'File types allowed for Image are : .jpg, .jpeg, .png, .svg, .gif';
        $error = $err3 . $err4;
        $err1 = 'Please upload pdf file only. ';
        $err2 = '';
        $error = $err1 . $err2;
        if (isset($ordermanualImg) && $ordermanualImg != '') {
            try {
                $allowed_file_types = [
                    'jpg',
                    'jpeg',
                    'png',
                    'svg',
                    'gif'
                ];
                $uploader = $this->uploader->create(['fileId' => 'ordermanualbanner']);
                if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                    $filename = '';
                    $allowedExtensionType = '';
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->save($targetImg);
                    $fileName = 'Information/Image' . $uploader->getUploadedFileName();
                    $data['ordermanualbanner'] = $fileName;
                } else {
                    $this->messageManager->addError(__($error));
                    if ($this->getRequest()->getParam('id')) {
                        $this->_redirect(
                            '*/*/edit',
                            ['id' => $this->getRequest()->getParam('id'), '_current' => true]
                        );
                        return;
                    }
                    $this->_redirect('*/*/');
                    return;
                }
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
            } //end try
        } //end if

        if (isset($orderPdf) && $orderPdf != '') {
            try {
                $allowed_file_types = [
                    'pdf'
                ];
                $uploader = $this->uploader->create(['fileId' => 'orderpdf']);
                if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                    $filename = '';
                    $allowedExtensionType = '';
                    $uploader->setAllowedExtensions(['pdf']);
                    $uploader->save($target);
                    $fileName = 'Information/Pdf/' . $uploader->getUploadedFileName();
                    $data['orderpdf'] = $fileName;
                } else {
                    $this->messageManager->addError(__($error));
                    if ($this->getRequest()->getParam('id')) {
                        $this->_redirect(
                            '*/*/edit',
                            ['id' => $this->getRequest()->getParam('id'), '_current' => true]
                        );
                        return;
                    }
                    $this->_redirect('*/*/');
                    return;
                }
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
            } //end try
        } //end if

        try {
            $id = $this->getRequest()->getParam('id');
            if (!$data) {
                $this->_redirect('informationform/ordermanual/new');
            }
            $model->setData($data)->setId($id);
            $model->save();
            $this->messageManager->addSuccess(__('Order Manual has been Succesfully Saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('informationform/ordermanual/new');
    }
}
