<?php

namespace Navigate\BannerSlider\Controller\Adminhtml\Bannerslider;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Io\File;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var boolean
     */
    protected $resultPageFactory = false;

    /**
     * @var UploaderFactory
     */
    protected $uploader;

    /**
     * @var DirectoryList
     */
    protected $_mediaDirectory;

    /**
     * @var \Navigate\BannerSlider\Model\BannersliderFactory
     */
    protected $bannersliderFactory;

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
     * @param \Navigate\BannerSlider\Model\BannersliderFactory         $bannersliderFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory         $uploader
     * @param \Magento\Framework\Filesystem                            $filesystem
     * @param File                                                     $fileIo
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Navigate\BannerSlider\Model\BannersliderFactory $bannersliderFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploader,
        \Magento\Framework\Filesystem $filesystem,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->bannersliderFactory = $bannersliderFactory;
        $this->uploader = $uploader;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        );
        $this->fileIo = $fileIo;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $target = $this->_mediaDirectory->getAbsolutePath('/Navigate/Slider/');
        $delImage = $this->_mediaDirectory->getAbsolutePath();
        $ImgFiles = $this->getRequest()->getFiles('imagename')['name'];
        $model = $this->bannersliderFactory->create();
        $err1 = 'Please upload valid type of format for Slider Image';
        $err2 = 'File types allowed for Image are : .jpg, .jpeg, .png, .svg, .gif';
        $error = $err1.$err2;
        try {
            if (isset($data['imagename']['delete'])) {
                $deleteImage = $delImage . $data['imagename']['value'];
                $data['imagename'] = '';
                $this->fileIo->rm($deleteImage);
            }

            if (isset($data['imagename']['value'])) {
                $data['imagename'] = $data['imagename']['value'];
            }

            // Mobile Image logic
            if (isset($data['mobileimagename']['delete'])) {
                $deleteMobileImage = $delImage . $data['mobileimagename']['value'];
                $data['mobileimagename'] = '';
                $this->fileIo->rm($deleteMobileImage);
            }

            if (isset($data['mobileimagename']['value'])) {
                $data['mobileimagename'] = $data['mobileimagename']['value'];
            }

            // end mobile logic
            if (isset($ImgFiles) && $ImgFiles != '') {
                try {
                    $allowed_file_types = [
                        'jpg',
                        'jpeg',
                        'png',
                        'svg',
                        'gif'
                    ];
                    $uploader = $this->uploader->create(['fileId' => 'imagename']);
                    if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                        $filename = '';
                        $allowedExtensionType = '';
                        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                        $uploader->setAllowRenameFiles(true);
                        $uploader->setFilesDispersion(true);
                        $uploader->save($target);
                        $fileName = 'Navigate/Slider' . $uploader->getUploadedFileName();
                        $data['imagename'] = $fileName;
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

            // Mobile image upload
            if (isset($MobileImgFiles) && $MobileImgFiles != '') {
                try {
                    $allowed_file_types = [
                        'jpg',
                        'jpeg',
                        'png',
                        'svg',
                        'gif'
                    ];
                    $uploader = $this->uploader->create(['fileId' => 'mobileimagename']);
                    if (in_array($uploader->getFileExtension(), $allowed_file_types)) {
                        $filename = '';
                        $allowedExtensionType = '';
                        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'svg', 'gif']);
                        $uploader->setAllowRenameFiles(true);
                        $uploader->setFilesDispersion(true);
                        $uploader->save($target);
                        $fileName = 'Navigate/Slider' . $uploader->getUploadedFileName();
                        $data['mobileimagename'] = $fileName;
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

            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
                if ($id != $model->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong item is specified.'));
                }
            }

            try {
                $model->setData($data)->setId($id);
                $model->save();
                $this->messageManager->addSuccess('Bannerslider succesfully added.');
            } catch (\Exception $e) {
                $this->messageManager->addError('Something went wrong while saving Bannerslider');
            }
        } catch (Exception $e) {
            $this->messageManager->addError('Something went wrong ' . $e->getMessage());
        } //end try

        $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if ($this->getRequest()->getParam('back')) {
            return $resultRedirect->setPath(
                '*/*/edit',
                [
                    'id' => $model->getId(),
                    '_current' => true,
                ]
            );
        }

        $resultRedirect->setPath('bannerslider/bannerslider/index');
        return $resultRedirect;
    } //end execute()
} //end class
