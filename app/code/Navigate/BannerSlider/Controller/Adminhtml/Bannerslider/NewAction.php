<?php

namespace Navigate\BannerSlider\Controller\Adminhtml\Bannerslider;

class NewAction extends \Magento\Backend\App\Action
{
    /**
     * @var ForwardFactory
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
    } //end __construct()

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    } //end execute()
} //end class
