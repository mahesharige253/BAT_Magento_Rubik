<?php

namespace Bat\RequisitionList\Controller\Adminhtml\RequisitionList;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\TestFramework\ErrorLog\Logger;

/**
 * @class ProductsGrid
 * For filtering in product grid
 */
class ProductsGrid extends Action
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
        $resultLayout->getLayout()->getBlock('requisitionlist.edit.tab.products')
                     ->setInBanner($this->getRequest()->getPost('requisitionlist_products', null));
        return $resultLayout;
    }
}
