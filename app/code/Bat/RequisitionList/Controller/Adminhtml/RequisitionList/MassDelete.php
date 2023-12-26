<?php
namespace Bat\RequisitionList\Controller\Adminhtml\RequisitionList;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin\CollectionFactory as RequisitionListCollectionFactory;

class MassDelete extends Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var RequisitionListCollectionFactory
     */
    protected $collectionFactory;

    /**
     * MassDelete constructor.
     * @param Action\Context $context
     * @param Filter $filter
     * @param RequisitionListCollectionFactory $collectionFactory
     */
    public function __construct(
        Action\Context $context,
        Filter $filter,
        RequisitionListCollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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
     * Mass delete
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $requisitionListItem = $this->filter->getCollection($this->collectionFactory->create());
            $itemsDeleted = 0;
            foreach ($requisitionListItem as $item) {
                $item->delete();
                $itemsDeleted++;
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 Requisition List(s) were deleted.', $itemsDeleted)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('requisitionlist/requisitionlist');
    }
}
