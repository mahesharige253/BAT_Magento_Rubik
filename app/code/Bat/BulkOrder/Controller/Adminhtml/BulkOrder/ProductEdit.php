<?php
 
namespace Bat\BulkOrder\Controller\Adminhtml\BulkOrder;
 
use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class ProductEdit extends Action
{
    /**
     * Execute function

     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = __('Select Products');
        $resultPage->getConfig()->getTitle()->prepend($title);

        $params = $this->getRequest()->getParams();

        return $resultPage;
    }
}
