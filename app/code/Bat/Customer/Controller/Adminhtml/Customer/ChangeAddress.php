<?php

namespace Bat\Customer\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;


/**
 * @class ForogotPasswordPin
 * ForogotPasswordPin
 */
class ChangeAddress extends Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ManagerInterface $messageManager
     * @param Data $data
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ManagerInterface $messageManager,
        Data $data,
        CustomerRepositoryInterface $customerRepositoryInterface

    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $messageManager;
        $this->data = $data;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * Returns the Change Address URL of Customer
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $outletId = '';
        $mobileNumber = '';
        try {
            $customerId = $this->getRequest()->getParam('customer_id');
            $customerData = $this->customerRepositoryInterface->getById($customerId);
            $mobileNumber = $customerData->getCustomAttribute('mobilenumber')-> getValue();
            $outletId =  $customerData->getCustomAttribute('outlet_id')->getValue();
            $this->data->sendAddressChangeKakao($mobileNumber,$outletId);
            return $resultRedirect->setPath('customer/index/edit/id/' . $customerId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addNotice(__("Something wrong, please try again."));
        }
        return $resultRedirect;
    }
}