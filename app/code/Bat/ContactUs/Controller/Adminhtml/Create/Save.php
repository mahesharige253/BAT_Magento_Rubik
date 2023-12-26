<?php

namespace Bat\ContactUs\Controller\Adminhtml\Create;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

class Save extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_contactus';

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
     * @var ContactUsFormFactory
     */
    protected $contactusformFactory;

    /**
     * @var ResultFactory
     */
    protected $_resultFactory;

    /**
     * @var File
     */
    protected $fileIo;

     /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context                      $context
     * @param \Bat\ContactUs\Model\ContactUsFormFactory            $contactusformFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory         $uploader
     * @param \Magento\Framework\Filesystem                            $filesystem
     * @param File                                                     $fileIo
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bat\ContactUs\Model\ContactUsFormFactory $contactusformFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Framework\Filesystem $filesystem,
        FormFactory $formFactory,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->contactusformFactory = $contactusformFactory;
        $this->uploader = $uploader;
        $this->formFactory = $formFactory;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->fileIo = $fileIo;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $bannerImage = '';
        $target = $this->_mediaDirectory->getAbsolutePath('/ContactUs/Image/');
        $bannerImage = $this->getRequest()->getFiles('bannerimage')['name'];
        if (isset($data['bannerimage']) && isset($data['bannerimage']['value'])) {
            $data['bannerimage'] = $data['bannerimage']['value'];
        }
        if (isset($data['bannerimage']) && isset($data['bannerimage']['delete'])) {
            if ($data['bannerimage']['delete'] == 1) {
                unset($data['bannerimage']);
                $data['bannerimage'] = '';
            }
        }
        $model = $this->contactusformFactory->create();
        $err1 = 'Please upload valid type of format for Slider Image';
        $err2 = 'File types allowed for Image are : .jpg, .jpeg, .png, .svg, .gif';
        $error = $err1 . $err2;
        if (isset($bannerImage) && $bannerImage != '') {
            try {
                $allowed_file_types = [
                    'jpg',
                    'jpeg',
                    'png',
                    'svg',
                    'gif'
                ];
                $uploader = $this->uploader->create(['fileId' => 'bannerimage']);
                if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                    $filename = '';
                    $allowedExtensionType = '';
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->save($target);
                    $fileName = 'ContactUs/Image' . $uploader->getUploadedFileName();
                    $data['bannerimage'] = $fileName;
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
                $this->_redirect('contactusform/create/new');
            }
            $model->setData($data)->setId($id);
            $model->save();
            $this->messageManager->addSuccess(__('ContactUs form has been Succesfully Saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('contactusform/create/new');
    }
}
