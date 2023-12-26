<?php

namespace Bat\Information\Controller\Adminhtml\Createfaq;

class NewAction extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Bat_Information::menu_faq';

    /**
     * @var $resultForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * NewAction constructor.
     *
     * @param \Magento\Backend\App\Action\Context               $context
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }//end __construct()

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }//end execute()
}//end class
