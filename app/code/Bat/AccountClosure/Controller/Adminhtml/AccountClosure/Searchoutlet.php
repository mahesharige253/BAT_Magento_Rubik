<?php
 
namespace Bat\AccountClosure\Controller\Adminhtml\AccountClosure;
 
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
        $title = __('Account Closure Request');
        $resultPage->getConfig()->getTitle()->prepend($title);

        $params = $this->getRequest()->getParams();

        return $resultPage;
    }
}
