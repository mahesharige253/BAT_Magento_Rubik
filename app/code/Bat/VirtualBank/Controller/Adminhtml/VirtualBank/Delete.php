<?php

namespace Bat\VirtualBank\Controller\Adminhtml\VirtualBank;

use Bat\VirtualBank\Controller\Adminhtml\Listing;
use Magento\Framework\Controller\ResultInterface;

/**
 * @class Delete
 * Delete Bank
 */
class Delete extends Listing
{
    protected const ACL_RESOURCE = 'Bat_VirtualBank::delete';

    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->_authorization->isAllowed(self::ACL_RESOURCE)) {
            $this->messageManager->addErrorMessage('Action not allowed');
            return $resultRedirect->setPath('*/*/');
        }
        $requestId = $this->getRequest()->getParam('bank_id');
        if ($requestId) {
            try {
                $model = $this->bankModelFactory->create();
                $model->load($requestId);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('Bank deleted successfully'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
        }
    }
}
