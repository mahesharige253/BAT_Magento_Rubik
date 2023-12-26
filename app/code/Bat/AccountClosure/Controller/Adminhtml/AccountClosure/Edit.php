<?php
namespace Bat\AccountClosure\Controller\Adminhtml\AccountClosure;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\ObjectManager;
use Bat\RequisitionList\Model\RequisitionListAdminFactory;
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
      * @var RequisitionListAdminFactory
      */
    protected $requisitionListAdminFactory;

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
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        RequisitionListAdminFactory $requisitionListAdminFactory,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $registry;
        $this->requisitionListAdminFactory = $requisitionListAdminFactory ?:
                ObjectManager::getInstance()->get(RequisitionListAdminFactory::class);
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
            __('Account Closure'),
            __('Account Closure')
        )->addBreadcrumb(
            __('Manage Account Closure'),
            __('Manage Account Closure')
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
            __('Account Closure Request'),
            __('Account Closure Request')
        );
        $resultPage->getConfig()->getTitle()
        ->prepend(__('Account Closure Request'));
        return $resultPage;
    }
}
