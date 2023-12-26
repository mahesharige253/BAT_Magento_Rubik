<?php

namespace Bat\Information\Controller\Adminhtml\Create;

use Bat\Information\Model\InformationFormFactory;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_productbarcode';

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
    protected $informationformFactory;

    /**
     * Intialize construct
     *
     * @return void
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param InformationFormFactory $informationformFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        InformationFormFactory $informationformFactory
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationformFactory = $informationformFactory;
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
                $model = $this->informationformFactory->create();
                $model->load($id);
                $data = $model->getData();
                $model->delete();
                $this->messageManager->addSuccess(__('Product Barcode Form deleted successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong ' . $e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('Product Barcode not found, please try once more.'));
        }
        $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('informationform/create/index');
        return $resultRedirect;
    }
}
