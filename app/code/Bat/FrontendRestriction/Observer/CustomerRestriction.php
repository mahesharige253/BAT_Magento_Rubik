<?php

namespace Bat\FrontendRestriction\Observer;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResponseFactory;
use Bat\CustomerGraphQl\Helper\Data;

class CustomerRestriction implements ObserverInterface
{
    /**
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * CustomerRestriction constructor.
     *
     * @param RedirectInterface $redirect
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param ResponseFactory $response
     * @param Data $helper
     */
    public function __construct(
        RedirectInterface $redirect,
        State $state,
        StoreManagerInterface $storeManager,
        ResponseFactory $response,
        Data $helper
    ) {
        $this->redirect = $redirect;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->response = $response;
        $this->helper = $helper;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        // Only restrict the frontend pages
        if ($this->getArea() === 'frontend') {
            $customRedirectionUrl = $this->helper->getFrontendBaseUrl();
            $this->response->create()
                        ->setRedirect($customRedirectionUrl)
                        ->sendResponse();
            exit(0);
            return $this;
        }
    }

    /**
     * Get Area
     *
     * @return mixed
     * @throws LocalizedException
     */
    private function getArea()
    {
        return $this->state->getAreaCode();
    }
}
