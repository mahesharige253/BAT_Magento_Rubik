<?php
namespace Bat\JokerOrder\Controller\Adminhtml\Customer\Action\Attribute;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Bat\JokerOrder\Controller\Adminhtml\Customer\Action\Attribute as AttributeAction;

/**
 * Form for mass updatings customers' attributes.
 * Can be accessed by GET since it's a form,
 * can be accessed by POST since it's used as a processor of a mass-action button.
 */
class Ecall extends AttributeAction implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * MassActions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param Context $context
     * @param \Bat\JokerOrder\Helper\Customer\Edit\Action\Attribute $attributeHelper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bat\JokerOrder\Helper\Customer\Edit\Action\Attribute $attributeHelper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $attributeHelper);
    }

    /**
     * Joker Order Ecall
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('filters')) {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $this->attributeHelper->setCustomerIds($collection->getAllIds());
        }

        if (!$this->_validateCustomers()) {
            return $this->resultRedirectFactory->create()->setPath('customer/', ['_current' => true]);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Joker Order'));
        return $resultPage;
    }

    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bat_JokerOrder::jokerorder');
    }
}
