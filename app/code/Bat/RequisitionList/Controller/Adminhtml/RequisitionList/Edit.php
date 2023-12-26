<?php
namespace Bat\RequisitionList\Controller\Adminhtml\RequisitionList;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;
use Magento\Framework\App\ObjectManager;
use Bat\RequisitionList\Model\RequisitionListAdminFactory;

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
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        RequisitionListAdminFactory $requisitionListAdminFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $registry;
        $this->requisitionListAdminFactory = $requisitionListAdminFactory ?:
                ObjectManager::getInstance()->get(RequisitionListAdminFactory::class);
        $this->backSession = $context->getSession();
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
            'Bat_RequisitionList::requisitionlist'
        )->addBreadcrumb(
            __('Requisition List'),
            __('Requisition List')
        )->addBreadcrumb(
            __('Manage Requisition List'),
            __('Manage Requisition List')
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
        $id = $this->getRequest()->getParam('entity_id');
        $requisitionListAdminModel = $this->requisitionListAdminFactory->create();

        if ($id) {
            $requisitionListAdminModel->load($id);
            if (!$requisitionListAdminModel->getId()) {
                $this->messageManager->addError(__('This condition no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $data = $this->backSession->getFormData(true);
        
        if (!empty($data)) {
            $requisitionListAdminModel->setData($data);
        }

        $this->coreRegistry->register('requisitionlist', $requisitionListAdminModel);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Requisition List') : __('New Requisition List'),
            $id ? __('Edit Requisition List') : __('New Requisition List')
        );
        $resultPage->getConfig()->getTitle()
        ->prepend($requisitionListAdminModel->getEntityId() ?
                    $requisitionListAdminModel->getName() : __('New Requisition List'));
        return $resultPage;
    }
}
