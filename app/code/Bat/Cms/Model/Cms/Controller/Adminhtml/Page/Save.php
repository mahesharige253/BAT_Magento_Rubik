<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\Cms\Model\Cms\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Bat\Cms\Model\ResourceModel\CmsPageVersion as CmsPageVersionResource;
use Bat\Cms\Model\CmsPageVersionModelFactory;

/**
 * Save CMS page action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Cms::save';

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var CmsPageVersionResource
     */
    private CmsPageVersionResource $cmsPageVersionResource;

    /**
     * @var CmsPageVersionModelFactory
     */
    private CmsPageVersionModelFactory $cmsPageVersionModelFactory;

    /**
     * @param Action\Context $context
     * @param PostDataProcessor $dataProcessor
     * @param DataPersistorInterface $dataPersistor
     * @param CmsPageVersionResource $cmsPageVersionResource
     * @param CmsPageVersionModelFactory $cmsPageVersionModelFactory
     * @param PageFactory|null $pageFactory
     * @param PageRepositoryInterface|null $pageRepository
     */
    public function __construct(
        Action\Context $context,
        PostDataProcessor $dataProcessor,
        DataPersistorInterface $dataPersistor,
        CmsPageVersionResource $cmsPageVersionResource,
        CmsPageVersionModelFactory $cmsPageVersionModelFactory,
        PageFactory $pageFactory = null,
        PageRepositoryInterface $pageRepository = null
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->dataPersistor = $dataPersistor;
        $this->pageFactory = $pageFactory ?: ObjectManager::getInstance()->get(PageFactory::class);
        $this->pageRepository = $pageRepository ?: ObjectManager::getInstance()->get(PageRepositoryInterface::class);
        $this->cmsPageVersionResource = $cmsPageVersionResource;
        $this->cmsPageVersionModelFactory = $cmsPageVersionModelFactory;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $data = $this->dataProcessor->filter($data);
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = Page::STATUS_ENABLED;
            }
            if (empty($data['page_id'])) {
                $data['page_id'] = null;
            }

            /** @var Page $model */
            $model = $this->pageFactory->create();

            $id = $this->getRequest()->getParam('page_id');
            if ($id) {
                try {
                    $model = $this->pageRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This page no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $data['layout_update_xml'] = $model->getLayoutUpdateXml();

            $pageVersion = 1.0;
            $saveVersion = 0;
            $versionData = [];
            if ($id) {
                if ($data['create_version']) {
                    $versionData = [
                        'page_reference_id' => $model->getId(),
                        'page_version' => $model->getPageVersion(),
                        'content_heading' => $model->getContentHeading(),
                        'version_title' => $model->getVersionTitle(),
                        'content' => $model->getContent(),
                        'updated_at' => $model->getUpdateTime()
                    ];
                    $pageVersion = $model->getPageVersion();
                    $pageVersion = $pageVersion + 0.10;
                    $saveVersion = 1;
                } else {
                    $pageVersion = $model->getPageVersion();
                }
            }
            $data['page_version'] = $pageVersion;
            $data['custom_layout_update_xml'] = $model->getCustomLayoutUpdateXml();
            $model->setData($data);

            try {
                $this->_eventManager->dispatch(
                    'cms_page_prepare_save',
                    ['page' => $model, 'request' => $this->getRequest()]
                );
                if ($saveVersion) {
                    $this->saveCmsPageVersion($versionData);
                }
                $this->pageRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the page.'));
                return $this->processResultRedirect($model, $resultRedirect, $data);
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (\Throwable $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the page.'));
            }

            $this->dataPersistor->set('cms_page', $data);
            return $resultRedirect->setPath('*/*/edit', ['page_id' => $this->getRequest()->getParam('page_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Process result redirect
     *
     * @param PageInterface $model
     * @param Redirect $resultRedirect
     * @param array $data
     * @return Redirect
     * @throws LocalizedException
     */
    private function processResultRedirect($model, $resultRedirect, $data)
    {
        if ($this->getRequest()->getParam('back', false) === 'duplicate') {
            $newPage = $this->pageFactory->create(['data' => $data]);
            $newPage->setId(null);
            $identifier = $model->getIdentifier() . '-duplicate-' . uniqid();
            $newPage->setIdentifier($identifier);
            $newPage->setIsActive(false);
            $newPage->setPageVersion(1);
            $this->pageRepository->save($newPage);
            $this->messageManager->addSuccessMessage(__('You duplicated the page.'));
            return $resultRedirect->setPath(
                '*/*/edit',
                [
                    'page_id' => $newPage->getId(),
                    '_current' => true,
                ]
            );
        }
        $this->dataPersistor->clear('cms_page');
        if ($this->getRequest()->getParam('back')) {
            return $resultRedirect->setPath('*/*/edit', ['page_id' => $model->getId(), '_current' => true]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Save CmsPage Version
     *
     * @param Array $versionData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveCmsPageVersion($versionData)
    {
        $versionData = $this->cmsPageVersionModelFactory->create()->setData($versionData);
        $this->cmsPageVersionResource->save($versionData);
    }
}
