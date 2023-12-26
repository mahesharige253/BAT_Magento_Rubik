<?php
namespace Bat\Rma\Controller\Adminhtml\CreateReturns;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * @clas Edit
 * Edit page for RequisitionList
 */
class Edit extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

     /**
      * @var Registry
      */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backSession;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

     /**
      * @var DataPersistorInterface
      */
    protected $getDataPersistor;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $registry;
        $this->backSession = $context->getSession();
        $this->_customerFactory = $customerFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->getDataPersistor = $dataPersistor;
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function _initAction()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(
            'Bat_AccountClosure::accountclosure'
        )->addBreadcrumb(
            __('Create Returns'),
            __('Create Returns')
        )->addBreadcrumb(
            __('Create Returns'),
            __('Create Returns')
        );
        return $resultPage;
    }

    /**
     * Return Requisition List page for edit
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {

        $id = $this->getRequest()->getParam('id');

        if ($id) {
            $this->getDataPersistor->set('id', $id);
        } else {
            $this->getDataPersistor->set('id', '');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            __('Create New Return'),
            __('Create New Return')
        );
        $resultPage->getConfig()->getTitle()
        ->prepend(__('Create New Return'));
        return $resultPage;
    }
}
