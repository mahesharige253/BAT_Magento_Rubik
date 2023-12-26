<?php

namespace Bat\VirtualBank\Controller\Adminhtml\VirtualBank;

use Bat\VirtualBank\Controller\Adminhtml\Listing;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @MassDelete
 * MassDelete Selected Banks
 */
class MassDelete extends Listing
{
    protected const ACL_RESOURCE = 'Bat_VirtualBank::delete';

    /**
     * MassDelete Action
     *
     * @return ResponseInterface|Redirect|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->_authorization->isAllowed(self::ACL_RESOURCE)) {
            $this->messageManager->addErrorMessage('Action not allowed');
            return $resultRedirect->setPath('*/*/');
        }
        $collection = $this->filter->getCollection($this->bankCollectionFactory->create());
        $count = 0;
        foreach ($collection as $child) {
            $child->delete();
            $count++;
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $count));
        return $resultRedirect->setPath('*/*/');
    }
}
