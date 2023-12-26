<?php

namespace Bat\Information\Controller\Adminhtml\Createnotice;

use Bat\Information\Model\InformationNoticeFormFactory;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_notice';

    /**
     * @var boolean
     */
    protected $resultPageFactory = false;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

     /**
      * @var ResultFactory
      */
    protected $_resultFactory;

    /**
     * @var InformationFormFactory
     */
    protected $informationNoticeFormFactory;

    /**
     * Intialize construct
     *
     * @return void
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param InformationNoticeFormFactory $informationNoticeFormFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        InformationNoticeFormFactory $informationNoticeFormFactory
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationNoticeFormFactory = $informationNoticeFormFactory;
        $this->messageManager = $messageManager;
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->informationNoticeFormFactory->create();
                $model->load($id);
                $data = $model->getData();
                $model->delete();
                $this->messageManager->addSuccess(__('Notice deleted successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong ' . $e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('Notice not found, please try once more.'));
        }
        $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('informationform/createnotice/index');
        return $resultRedirect;
    }
}
