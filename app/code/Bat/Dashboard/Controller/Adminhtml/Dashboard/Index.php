<?php

namespace Bat\Dashboard\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * @class Index
 * Bat Dashboard Index page
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    private PageFactory $pageFactory;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    /**
     * Is Allowed
     *
     * @inheritdoci
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bat_Dashboard::batdashboard');
    }

    /**
     * Bat Dashboard Index page
     *
     * @return ResultInterface|Page
     */
    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Dashboard'));
        return $page;
    }
}
