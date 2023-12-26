<?php

namespace Bat\Information\Controller\Adminhtml\Createbrand;

class Index extends \Magento\Backend\App\Action
{

        const ADMIN_RESOURCE = 'Bat_Information::menu_brand';

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
            $resultPage->getConfig()->getTitle()->prepend(__('Brand Lists'));
            $resultPage->setActiveMenu('Bat_Information::informationform');
            $resultPage->addBreadcrumb(__('Brand Form'), __('Brand Form'));
            return $resultPage;
    } //end execute()
} //end class
