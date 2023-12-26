<?php

namespace Navigate\BannerSlider\Controller\Adminhtml\Bannerslider;

class Index extends \Magento\Backend\App\Action
{

    /**
     * @var PageFactory
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
        $resultPage->getConfig()->getTitle()->prepend(__('Homepage Banner Slider List'));
        $resultPage->setActiveMenu('Navigate_BannerSlider::bannerslider');
        $resultPage->addBreadcrumb(__('Homepage Banner Slider'), __('Homepage Banner Slider'));
        return $resultPage;
    } //end execute()
} //end class
