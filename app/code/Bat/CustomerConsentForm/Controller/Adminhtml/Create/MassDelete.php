<?php

namespace Bat\CustomerConsentForm\Controller\Adminhtml\Create;

class MassDelete extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $_filter;

    /**
     * @var \Bat\CustomerConsentForm\Model\ResourceModel\ConsentForm\CollectionFactory
     */
    protected $_collectionFactory;

   /**
    * Data Construct
    *
    * @param Filter $filter
    * @param CollectionFactory $collectionFactory
    * @param Context $context
    */
    public function __construct(
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Bat\CustomerConsentForm\Model\ResourceModel\ConsentForm\CollectionFactory $collectionFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_filter = $filter;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context);
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        try {
            $collection = $this->_filter->getCollection($this->_collectionFactory->create());
            $itemsDelete = 0;
            foreach ($collection as $item) {
                $data = $item->getData();
                $item->delete();
                $itemsDelete++;
            }

            $this->messageManager->addSuccess(
                __(
                    'A total of %1 ConsentForms(s) were deleted successfully.',
                    $itemsDelete
                )
            );
        } catch (Exception $e) {
            $this->messageManager->addError(__('Something went wrong while deleting the ConsentForms ' . $e->getMessage()));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('customerconsentform/create/index');
    } //end execute()
} //end class
