<?php
namespace Bat\RequisitionList\Controller\Adminhtml\RequisitionList;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\LayoutFactory;

class Products extends Action
{
    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * Products constructor.
     *
     * @param Action\Context $context
     * @param LayoutFactory $resultLayoutFactory
     */
    public function __construct(
        Action\Context $context,
        LayoutFactory $resultLayoutFactory
    ) {
        parent::__construct($context);
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    /**
     * Is allowed
     *
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bat_RequisitionList::requisitionlist');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getLayout()->getBlock('requisitionlist.edit.tab.products')
                     ->setInProducts($this->getRequest()->getPost('index_products', null));
        return $resultLayout;
    }
}
