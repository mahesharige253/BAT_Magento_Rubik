<?php

namespace Bat\Rma\Controller\Adminhtml\CreateReturns;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class AddForm
 */
class Searchoutlet extends Action
{
    /**
     * Execute function

     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = __('Create Returns');
        $resultPage->getConfig()->getTitle()->prepend($title);
        $params = $this->getRequest()->getParams();
        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @inheritdoci
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bat_Rma::createRma');
    }
}
