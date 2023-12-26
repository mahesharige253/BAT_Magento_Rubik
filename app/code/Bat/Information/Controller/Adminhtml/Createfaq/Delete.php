<?php

namespace Bat\Information\Controller\Adminhtml\Createfaq;

use Bat\Information\Model\InformationFaqFormFactory;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_faq';

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
    protected $informationFaqFormFactory;

    /**
     * Intialize construct
     *
     * @return void
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param InformationFaqFormFactory $informationFaqFormFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        InformationFaqFormFactory $informationFaqFormFactory
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationFaqFormFactory = $informationFaqFormFactory;
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
                $model = $this->informationFaqFormFactory->create();
                $model->load($id);
                $data = $model->getData();
                $model->delete();
                $this->messageManager->addSuccess(__('Faq deleted successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong ' . $e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('Faq not found, please try once more.'));
        }
        $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('informationform/createfaq/index');
        return $resultRedirect;
    }
}
