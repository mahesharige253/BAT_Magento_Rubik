<?php

namespace Bat\Information\Controller\Adminhtml\Createnotice;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_notice';

        /**
         * @var $resultPageFactory
         */
        protected $resultPageFactory = false;
        /**
         * Index constructor.
         *
         * @param \Magento\Backend\App\Action\Context        $context
         * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
         */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
            parent::__construct($context);
            $this->resultPageFactory = $resultPageFactory;
    } //end __construct()
        
        /**
         * Execute Function
         */
    public function execute()
    {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Notice Lists'));
            $resultPage->setActiveMenu('Bat_Information::informationform');
            $resultPage->addBreadcrumb(__('Notice Form'), __('Notice Form'));
            return $resultPage;
    } //end execute()
} //end class
