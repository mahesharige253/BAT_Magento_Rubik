<?php

namespace Bat\NewProduct\Controller\Adminhtml\NewProduct;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\TestFramework\ErrorLog\Logger;

class Products extends Action
{

    /**
     * @var LayoutFactory
     */
    protected $_resultLayoutFactory;

    /**
     * @param Action\Context $context
     * @param LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        Action\Context $context,
        LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($context);
        $this->_resultLayoutFactory = $resultLayoutFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultLayout = $this->_resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock('newproduct.edit.tab.products')
                     ->setInProducts($this->getRequest()->getPost('newproduct_products', null));
        return $resultLayout;
    }
}
