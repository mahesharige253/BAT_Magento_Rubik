<?php

namespace Bat\Customer\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Message\ManagerInterface;
use Bat\Kakao\Model\Sms as KakaoSms;

class ForgotOutletId extends Action
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRegistry;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

     /**
     * @var KakaoSms
     */
    protected $kakaoSms;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param Customer $customer
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerRepositoryInterface $customerRepository,
        Customer $customer,
        ManagerInterface $messageManager,
        KakaoSms $kakaoSms
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->customerRegistry = $customerRepository;
        $this->customer = $customer;
        $this->messageManager = $messageManager;
        $this->kakaoSms = $kakaoSms;
    }

    /**
     * Returns the OutletId of the Customer
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $customerId = $this->getRequest()->getParam('customer_id');
            $customer = $this->customerRegistry->getById($customerId);
            $outletId = $customer->getCustomAttribute('outlet_id')->getValue();
            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            $this->kakaoSms->sendSms($mobileNumber, ['outlet_id' => $outletId], 'ForgotID_001');
            $this->messageManager->addSuccess(__("Kakao Message Sent Successfully"));
            return $resultRedirect->setPath('customer/index/edit/id/' . $customerId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addNotice(__("Something wrong, please try again."));
        }
        return $resultRedirect;
    }
}
