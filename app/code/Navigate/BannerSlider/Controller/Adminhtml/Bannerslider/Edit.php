<?php

namespace Navigate\BannerSlider\Controller\Adminhtml\Bannerslider;

use Navigate\BannerSlider\Model\BannersliderFactory;
use Magento\Framework\Registry;

class Edit extends \Magento\Backend\App\Action
{

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory = null;

    /**
     * @var BannersliderFactory
     */
    protected $bannersliderFactory = null;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param Registry                                   $registry
     * @param BannersliderFactory                        $bannersliderFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Registry $registry,
        BannersliderFactory $bannersliderFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory   = $resultPageFactory;
        $this->_coreRegistry       = $registry;
        $this->bannersliderFactory = $bannersliderFactory;
    }//end __construct()

    /**
     * Execute Function
     */
    public function execute()
    {
        $bannerslider = $this->getRequest()->getParam('id');
        $model        = $this->bannersliderFactory->create();
        $model->load($bannerslider);
        $this->_coreRegistry->register('bannerslider', $model);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Navigate_BannerSlider::bannerslider');
        $resultPage->getConfig()->getTitle()->prepend(
            $bannerslider ? __('Edit Banner "'.$model->getTitle().'"') : __('New Bannerslider')
        );
        return $resultPage;
    }//end execute()
}//end class
