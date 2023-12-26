<?php

namespace Bat\CustomerConsentForm\Controller\Adminhtml\Create;

use Bat\CustomerConsentForm\Model\ConsentFormFactory;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\Backend\App\Action
{
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
     * @var ConsentFormFactory
     */
    protected $consentformFactory;

    /**
     * Intialize construct
     *
     * @return void
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param ConsentFormFactory $consentformFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        ConsentFormFactory $consentformFactory
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->consentformFactory = $consentformFactory;
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
                $model = $this->consentformFactory->create();
                $model->load($id);
                $data = $model->getData();
                $model->delete();
                $this->messageManager->addSuccess(__('ConsentForm deleted successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong ' . $e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('ConsentForm not found, please try once more.'));
        }
        $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('customerconsentform/create/index');
        return $resultRedirect;
    }
}
