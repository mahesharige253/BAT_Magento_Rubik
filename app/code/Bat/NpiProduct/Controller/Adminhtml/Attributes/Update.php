<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\NpiProduct\Controller\Adminhtml\Attributes;

use Magento\Backend\App\Action\Context;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute as AttributeAction;

/**
 * @class Update
 * Form for mass updating Product Tags.
 */
class Update extends AttributeAction implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * MassActions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param Attribute $attributeHelper
     * @param PageFactory $resultPageFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Attribute $attributeHelper,
        PageFactory $resultPageFactory,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $attributeHelper);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('filters')) {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $this->attributeHelper->setProductIds($collection->getAllIds());
        }
        if (!$this->_validateProducts()) {
            return $this->resultRedirectFactory->create()->setPath('catalog/product/', ['_current' => true]);
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Update Product Tags'));
        return $resultPage;
    }
}
