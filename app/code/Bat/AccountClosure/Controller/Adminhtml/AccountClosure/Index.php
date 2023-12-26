<?php
declare(strict_types=1);

namespace Bat\AccountClosure\Controller\Adminhtml\AccountClosure;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends Action implements HttpGetActionInterface
{
   /**
    * Execute.
    *
    * @return PageFactory $resultPageFactory
    */
     
    public function execute()
    {
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        return $result;
    }
}
