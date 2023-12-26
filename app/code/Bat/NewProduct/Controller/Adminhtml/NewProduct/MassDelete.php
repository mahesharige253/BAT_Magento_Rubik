<?php

namespace Bat\NewProduct\Controller\Adminhtml\NewProduct;

use Bat\NewProduct\Controller\Adminhtml\Listing;
use Bat\NewProduct\Model\NewProductModelFactory;
use Bat\NewProduct\Model\ResourceModel\NewProductResource\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;

/**
 * @MassDelete
 * MassRemove Selected Products
 */
class MassDelete extends Listing
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
     * Mass delete action
     *
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws LocalizedException
     * MassDelete Action
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->newProductResourceCollectionFactory->create());
        $count = 0;
        try {
            foreach ($collection as $child) {
                $child->delete();
                $count++;
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }
}
