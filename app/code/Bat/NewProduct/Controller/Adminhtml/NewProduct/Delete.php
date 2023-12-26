<?php

namespace Bat\NewProduct\Controller\Adminhtml\NewProduct;

use Bat\NewProduct\Controller\Adminhtml\Listing;
use Bat\NewProduct\Model\NewProductModelFactory;
use Bat\NewProduct\Model\ResourceModel\NewProductResource\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * @class Delete
 * Remove product
 */
class Delete extends Listing
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @param Context $context
     * @param CollectionFactory $newProductResourceCollectionFactory
     * @param PageFactory $resultPageFactory
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param Filter $filter
     * @param NewProductModelFactory $newProductModelFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        CollectionFactory $newProductResourceCollectionFactory,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        Filter $filter,
        NewProductModelFactory $newProductModelFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
        parent::__construct(
            $context,
            $newProductResourceCollectionFactory,
            $resultPageFactory,
            $coreRegistry,
            $dataPersistor,
            $filter,
            $newProductModelFactory
        );
    }

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $requestId = $this->getRequest()->getParam('entity_id');
        if ($requestId) {
            try {
                $model = $this->newProductModelFactory->create();
                $model->load($requestId);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('Product removed successfully'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
        }
    }
}
