<?php

namespace Bat\Information\Controller\Adminhtml\Create;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Io\File;

class Save extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_productbarcode';

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
     * @var InformationFormFactory
     */
    protected $informationformFactory;

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
     * @param \Bat\Information\Model\InformationFormFactory            $informationformFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory         $uploader
     * @param \Magento\Framework\Filesystem                            $filesystem
     * @param File                                                     $fileIo
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bat\Information\Model\InformationFormFactory $informationformFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Framework\Filesystem $filesystem,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationformFactory = $informationformFactory;
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

        $productImg = '';
        $packBarcodeImg = '';
        $cartonBarcodeImg = '';
        $target = $this->_mediaDirectory->getAbsolutePath('/Information/Image/');
        $productImg = $this->getRequest()->getFiles('productimage')['name'];
        $packBarcodeImg = $this->getRequest()->getFiles('packbarcode')['name'];
        $cartonBarcodeImg = $this->getRequest()->getFiles('cartonbarcode')['name'];

   
        if (isset($data['productimage']) && isset($data['productimage']['value'])) {
            $data['productimage'] = $data['productimage']['value'];
        }
        if (isset($data['packbarcode']) && isset($data['packbarcode']['value'])) {
            $data['packbarcode'] = $data['packbarcode']['value'];
        }
        if (isset($data['cartonbarcode']) && isset($data['cartonbarcode']['value'])) {
            $data['cartonbarcode'] = $data['cartonbarcode']['value'];
        }
        if (isset($data['productimage']) && isset($data['productimage']['delete'])) {
            if ($data['productimage']['delete'] == 1) {
                unset($data['productimage']);
                $data['productimage'] = '';
            }
        }
        if (isset($data['packbarcode']) && isset($data['packbarcode']['delete'])) {
            if ($data['packbarcode']['delete'] == 1) {
                unset($data['packbarcode']);
                $data['packbarcode'] = '';
            }
        }
        if (isset($data['cartonbarcode']) && isset($data['cartonbarcode']['delete'])) {
            if ($data['cartonbarcode']['delete'] == 1) {
                unset($data['cartonbarcode']);
                $data['cartonbarcode'] = '';
            }
        }
        $model = $this->informationformFactory->create();
        $err1 = 'Please upload valid type of format for Slider Image';
        $err2 = 'File types allowed for Image are : .jpg, .jpeg, .png, .svg, .gif';
        $error = $err1 . $err2;
        
        if (isset($productImg) && $productImg != '') {
            try {
                $allowed_file_types = [
                    'jpg',
                    'jpeg',
                    'png',
                    'svg',
                    'gif'
                ];
                $uploader = $this->uploader->create(['fileId' => 'productimage']);
                if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                    $filename = '';
                    $allowedExtensionType = '';
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->save($target);
                    $fileName = 'Information/Image' . $uploader->getUploadedFileName();
                    $data['productimage'] = $fileName;
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

        if (isset($packBarcodeImg) && $packBarcodeImg != '') {
            try {
                $allowed_file_types = [
                    'jpg',
                    'jpeg',
                    'png',
                    'svg',
                    'gif'
                ];
                $uploader = $this->uploader->create(['fileId' => 'packbarcode']);
                if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                    $filename = '';
                    $allowedExtensionType = '';
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->save($target);
                    $fileName = 'Information/Image' . $uploader->getUploadedFileName();
                    $data['packbarcode'] = $fileName;
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

        if (isset($cartonBarcodeImg) && $cartonBarcodeImg != '') {
            try {
                $allowed_file_types = [
                    'jpg',
                    'jpeg',
                    'png',
                    'svg',
                    'gif'
                ];
                $uploader = $this->uploader->create(['fileId' => 'cartonbarcode']);
                if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                    $filename = '';
                    $allowedExtensionType = '';
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->save($target);
                    $fileName = 'Information/Image' . $uploader->getUploadedFileName();
                    $data['cartonbarcode'] = $fileName;
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
                $this->_redirect('informationform/create/index');
            }
            $model->setData($data)->setId($id);
            $model->save();
            $this->messageManager->addSuccess(__('Product Barcode has been Succesfully Saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('informationform/create/index');
    }
}
