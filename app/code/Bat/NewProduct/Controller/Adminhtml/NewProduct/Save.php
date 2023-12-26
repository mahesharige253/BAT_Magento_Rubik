<?php

namespace Bat\NewProduct\Controller\Adminhtml\NewProduct;

use Bat\NewProduct\Model\NewProductModelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Js;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

/**
 * @class Save
 * Save New Product
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var NewProductModelFactory
     */
    private NewProductModelFactory $newProductModelFactory;

    /**
     * @var Js
     */
    private Js $_jsHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param NewProductModelFactory $newProductModelFactory
     * @param Js $jsHelper
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        NewProductModelFactory $newProductModelFactory,
        Js $jsHelper,
        ProductRepositoryInterface $productRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->newProductModelFactory = $newProductModelFactory;
        $this->_jsHelper = $jsHelper;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    /**
     * Save Action
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            if (isset($data['products'])) {
                $productIds = $this->_jsHelper->decodeGridSerializedInput($data['products']);
                $this->saveProducts($productIds);
                $this->messageManager->addSuccessMessage(__('The Product has been successfully added'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error while trying to save data'.$e->getMessage()));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index', ['_current' => true]);
    }

    /**
     * Save New Products
     *
     * @param array $productIds
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function saveProducts($productIds)
    {
        $newProductFactoryObj = $this->newProductModelFactory->create();
        $newProductData = $newProductFactoryObj->getProducts();
        foreach ($productIds as $productId) {
            if (!in_array($productId, $newProductData)) {
                $product = $this->productRepository->getById($productId);
                $catArray = $product->getCategoryIds();
                $catImp = '';
                $catImp = implode(', ', $catArray);
                $data = [
                    'product_id'=>$productId,
                    'category_id' => $catImp
                ];
                $newProduct = $newProductFactoryObj->setData($data);
                $newProduct->save();
            }
        }
    }
}
