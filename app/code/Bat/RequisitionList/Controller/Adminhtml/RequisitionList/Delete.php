<?php
namespace Bat\RequisitionList\Controller\Adminhtml\RequisitionList;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Bat\RequisitionList\Model\RequisitionListAdminFactory;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin as RequisitionListResourceModel;

class Delete extends Action
{
    /**
     * @var RequisitionListAdminFactory
     */
    protected $requisitionListAdminFactory;

    /**
     * @var RequisitionListResourceModel
     */
    protected $requisitionListResourceModel;

    /**
     * Delete constructor.
     * @param Action\Context $context
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     * @param RequisitionListResourceModel $requisitionListResourceModel
     */
    public function __construct(
        Action\Context $context,
        RequisitionListAdminFactory $requisitionListAdminFactory,
        RequisitionListResourceModel $requisitionListResourceModel
    ) {
        $this->requisitionListAdminFactory = $requisitionListAdminFactory;
        $this->requisitionListResourceModel = $requisitionListResourceModel;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bat_RequisitionList::requisitionlist');
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $idFieldName = $this->requisitionListResourceModel->getIdFieldName();
        $requisitionListId = $this->getRequest()->getParam($idFieldName);
        
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($requisitionListId) {
            try {
                $model = $this->requisitionListAdminFactory->create();
                $this->requisitionListResourceModel->load($model, $requisitionListId);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This attachment no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
                $this->requisitionListResourceModel->delete($model);
                $message = __('The attachment %1 has been deleted', $model->getName());
                $this->messageManager->addSuccessMessage($message);
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['page_id' => $requisitionListId]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a attachment to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
