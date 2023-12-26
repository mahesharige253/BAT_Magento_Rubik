<?php

namespace Bat\Information\Controller\Adminhtml\Createbrand;

use Bat\Information\Model\InformationBrandFormFactory;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_brand';

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
     * @var InformationBrandFormFactory
     */
    protected $informationBrandFormFactory;

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
        InformationBrandFormFactory $informationBrandFormFactory
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationBrandFormFactory = $informationBrandFormFactory;
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
                $model = $this->informationBrandFormFactory->create();
                $model->load($id);
                $data = $model->getData();
                $model->delete();
                $this->messageManager->addSuccess(__('Brand deleted successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong ' . $e->getMessage()));
            }
        } else {
            $this->messageManager->addError(__('Brand not found, please try once more.'));
        }
        $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('informationform/createbrand/index');
        return $resultRedirect;
    }
}
