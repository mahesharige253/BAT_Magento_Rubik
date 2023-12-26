<?php

namespace Bat\ContactUs\Controller\Adminhtml\Create;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Bat_Information::menu_contactus';

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
            $resultPage->getConfig()->getTitle()->prepend(__('ContactUs Lists'));
            $resultPage->setActiveMenu('Bat_ContactUs::contactusform');
            $resultPage->addBreadcrumb(__('ContactUs Form'), __('ContactUs Form'));
            return $resultPage;
    } //end execute()
} //end class
