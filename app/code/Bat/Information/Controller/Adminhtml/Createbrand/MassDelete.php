<?php

namespace Bat\Information\Controller\Adminhtml\Createbrand;

class MassDelete extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Bat_Information::menu_brand';

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $_filter;

    /**
     * @var \Bat\Information\Model\ResourceModel\InformationBrandForm\CollectionFactory
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
        \Bat\Information\Model\ResourceModel\InformationBrandForm\CollectionFactory $collectionFactory,
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
                    'A total of %1 Brand(s) were deleted successfully.',
                    $itemsDelete
                )
            );
        } catch (Exception $e) {
            $this->messageManager->
                addError(__('Something went wrong while deleting the Notice ' . $e->getMessage()));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('informationform/createbrand/index');
    } //end execute()
} //end class
