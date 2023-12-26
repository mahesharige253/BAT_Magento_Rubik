<?php

namespace Bat\Information\Controller\Adminhtml\Createfaq;

use Magento\Framework\Controller\ResultFactory;


class Save extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Bat_Information::menu_faq';

    /**
     * @var boolean
     */
    protected $resultPageFactory = false;

    /**
     * @var InformationFormFactory
     */
    protected $informationFaqFormFactory;

    /**
     * @var ResultFactory
     */
    protected $_resultFactory;


    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context                      $context
     * @param \Bat\Information\Model\InformationFaqFormFactory      $informationFaqFormFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bat\Information\Model\InformationFaqFormFactory $informationFaqFormFactory
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationFaqFormFactory = $informationFaqFormFactory;
        
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $model = $this->informationFaqFormFactory->create();
        try {
            $id = $this->getRequest()->getParam('id');
            if (!$data) {
                $this->_redirect('informationform/createfaq/index');
            }
            $model->setData($data)->setId($id);
            $model->save();
            $this->messageManager->addSuccess(__('Faq has been Succesfully Saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('informationform/createfaq/index');
    }
}
