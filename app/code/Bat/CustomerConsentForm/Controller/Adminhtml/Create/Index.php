<?php

namespace Bat\CustomerConsentForm\Controller\Adminhtml\Create;

class Index extends \Magento\Backend\App\Action
{

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
            $resultPage->getConfig()->getTitle()->prepend(__('Consent Form Lists'));
            $resultPage->setActiveMenu('Bat_CustomerConsentForm::customerconsentform');
            $resultPage->addBreadcrumb(__('Customer Consent Form'), __('Customer Consent Form'));
            return $resultPage;
    } //end execute()
} //end class
