<?php

namespace Bat\Information\Controller\Adminhtml\Createnotice;

use Magento\Framework\Controller\ResultFactory;


class Save extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Bat_Information::menu_notice';

    /**
     * @var boolean
     */
    protected $resultPageFactory = false;

    /**
     * @var InformationFormFactory
     */
    protected $informationNoticeFormFactory;

    /**
     * @var ResultFactory
     */
    protected $_resultFactory;


    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context                      $context
     * @param \Bat\Information\Model\InformationNoticeFormFactory      $informationNoticeFormFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Bat\Information\Model\InformationNoticeFormFactory $informationNoticeFormFactory
    ) {
        parent::__construct($context);
        $this->_resultFactory = $context->getResultFactory();
        $this->informationNoticeFormFactory = $informationNoticeFormFactory;
        
    } //end __construct()

    /**
     * Execute function
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $model = $this->informationNoticeFormFactory->create();
        try {
            $id = $this->getRequest()->getParam('id');
            if (!$data) {
                $this->_redirect('informationform/createnotice/index');
            }
            $model->setData($data)->setId($id);
            $model->save();
            $this->messageManager->addSuccess(__('Notice has been Succesfully Saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('informationform/createnotice/index');
    }
}
