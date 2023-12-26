<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\NpiProduct\Controller\Adminhtml\Attributes;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * @class Save
 * Update Product Tags
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var Attribute
     */
    private Attribute $attributeHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Attribute $attributeHelper
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Attribute $attributeHelper,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->attributeHelper = $attributeHelper;
        $this->productRepository = $productRepository;
    }

    /**
     * Update action for Product Tag
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParams();
        try {
            $tags = [];
            $newTagChangeRequired = isset($data['change_new_product_tag']);
            $limitTagChangeRequired = isset($data['change_limited_product_tag']);
            if (!$newTagChangeRequired && !$limitTagChangeRequired) {
                throw new LocalizedException(__('No change in Product Tags'));
            }
            if (isset($data['new_product_tag'])) {
                $tags[] = 1;
            }
            if (isset($data['limited_product_tag'])) {
                $tags[] = 2;
            }
            $productIds = $this->attributeHelper->getProductIds();
            if (empty($productIds)) {
                throw new LocalizedException(__('Something went wrong. Please try again'));
            }
            foreach ($productIds as $productId) {
                $product = $this->productRepository->getById($productId);
                $product = $this->updateProductTags($tags, $product, $newTagChangeRequired, $limitTagChangeRequired);
                $this->productRepository->save($product);
            }
            $this->messageManager->addSuccessMessage('Product Tags updated successfully');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        return $resultRedirect->setPath('catalog/product/');
    }

    /**
     * Update product tags
     *
     * @param array $tags
     * @param ProductInterface $product
     * @param boolean $newTagChangeRequired
     * @param boolean $limitTagChangeRequired
     * @return mixed
     */
    public function updateProductTags($tags, $product, $newTagChangeRequired, $limitTagChangeRequired)
    {
        $productTag = $product->getCustomAttribute('product_tag');
        $productTags = [];
        if ($productTag) {
            $productTags = $productTag->getValue();
            $productTags = ($productTags != '') ? explode(',', $productTags) : [];
            $productTags = $this->setProductTags($productTags, $tags, $newTagChangeRequired, $limitTagChangeRequired);
        } else {
            $productTags = $this->setProductTags($productTags, $tags, $newTagChangeRequired, $limitTagChangeRequired);
        }
        $productTags = array_unique($productTags);
        sort($productTags);
        $product->setCustomAttribute('product_tag', $productTags);
        return $product;
    }

    /**
     * Set Product Tags
     *
     * @param array $productTags
     * @param array $tags
     * @param boolean $newTagChangeRequired
     * @param boolean $limitTagChangeRequired
     * @return mixed
     */
    public function setProductTags($productTags, $tags, $newTagChangeRequired, $limitTagChangeRequired)
    {
        if ($newTagChangeRequired && $limitTagChangeRequired) {
            $productTags = $tags;
        } elseif ($newTagChangeRequired) {
            if (in_array(1, $tags)) {
                $productTags[] = 1;
            } else {
                if (($key = array_search(1, $productTags)) !== false) {
                    unset($productTags[$key]);
                }
            }
        } elseif ($limitTagChangeRequired) {
            if (in_array(2, $tags)) {
                $productTags[] = 2;
            } else {
                if (($key = array_search(2, $productTags)) !== false) {
                    unset($productTags[$key]);
                }
            }
        }
        return $productTags;
    }
}
