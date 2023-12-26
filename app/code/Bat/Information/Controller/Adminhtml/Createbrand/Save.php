<?php

namespace Bat\Information\Controller\Adminhtml\Createbrand;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Io\File;

class Save extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Bat_Information::menu_brand';

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
    protected $informationBrandFormFactory;

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
        \Bat\Information\Model\InformationBrandFormFactory $informationBrandFormFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Framework\Filesystem $filesystem,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationBrandFormFactory = $informationBrandFormFactory;
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

        $brandImg = '';
        $target = $this->_mediaDirectory->getAbsolutePath('/Information/Image/');
        $brandImg = $this->getRequest()->getFiles('brandimage')['name'];

        if (isset($data['brandimage']) && isset($data['brandimage']['delete'])) {
            if ($data['brandimage']['delete'] == 1) {
                unset($data['brandimage']);
                $data['brandimage'] = '';
            }
        }
        if (isset($data['brandimage']) && isset($data['brandimage']['value'])) {
            $data['brandimage'] = $data['brandimage']['value'];
        }
   
        $model = $this->informationBrandFormFactory->create();
        $err1 = 'Please upload valid type of format for Slider Image';
        $err2 = 'File types allowed for Image are : .jpg, .jpeg, .png, .svg, .gif';
        $error = $err1 . $err2;
        if (isset($brandImg) && $brandImg != '') {
            try {
                $allowed_file_types = [
                    'jpg',
                    'jpeg',
                    'png',
                    'svg',
                    'gif'
                ];
                $uploader = $this->uploader->create(['fileId' => 'brandimage']);
                if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                    $filename = '';
                    $allowedExtensionType = '';
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->save($target);
                    $fileName = 'Information/Image' . $uploader->getUploadedFileName();
                    $data['brandimage'] = $fileName;
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
                $this->_redirect('informationform/createbrand/index');
            }
            $model->setData($data)->setId($id);
            $model->save();
            $this->messageManager->addSuccess(__('Brand has been Succesfully Saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('informationform/createbrand/index');
    }
}
