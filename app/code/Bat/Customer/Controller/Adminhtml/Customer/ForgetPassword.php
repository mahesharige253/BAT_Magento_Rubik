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
use Bat\Integration\Helper\Data;
use Bat\CustomerGraphQl\Helper\Data as CustomerHelper;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Customer\Helper\Data as HelperData;
use Magento\Framework\Exception\LocalizedException;
use Bat\PasswordHistory\Helper\Config;
use Bat\PasswordHistory\Model\UsedPasswordFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @class ForogotPasswordPin
 * ForogotPasswordPin
 */
class ForgetPassword extends Action
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
     * @var Data
     */
    protected $data;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var KakaoSms
     */
    protected $kakaoSms;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var UsedPasswordFactory
     */
    protected $usedPasswordFactory;

     /**
      * @var TimezoneInterface
      */
    private $timezoneInterface;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param Customer $customer
     * @param ManagerInterface $messageManager
     * @param Data $data
     * @param CustomerHelper $customerHelper
     * @param KakaoSms $kakaoSms
     * @param HelperData $helperData
     * @param UsedPasswordFactory $usedPasswordFactory
     * @param TimezoneInterface $timezoneInterface
     * @param Config $config
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CustomerRepositoryInterface $customerRepository,
        Customer $customer,
        ManagerInterface $messageManager,
        Data $data,
        CustomerHelper $customerHelper,
        KakaoSms $kakaoSms,
        HelperData $helperData,
        UsedPasswordFactory $usedPasswordFactory,
        TimezoneInterface $timezoneInterface,
        Config $config
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->customerRegistry = $customerRepository;
        $this->customer = $customer;
        $this->messageManager = $messageManager;
        $this->data = $data;
        $this->customerHelper = $customerHelper;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->usedPasswordFactory = $usedPasswordFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->config = $config;
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
            $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $usedPasswordFactory = $this->usedPasswordFactory->create();
            $lastUpdatePasswordTime = $usedPasswordFactory->getLastItemDateTime($customerId);
            if (!empty($lastUpdatePasswordTime)) {
                $hours = $this->config->getAllowedHours();
                $newDate = date('Y-m-d H:i:s', strtotime($lastUpdatePasswordTime[0]. ' + '.$hours.' hours'));
                if ($newDate >= $currentDateTime) {
                    $this->messageManager->addError(__('You can update new password after %1', $newDate));
                    return $resultRedirect->setPath('customer/index/edit/id/' . $customerId);
                }
            }
            $customer = $this->customerRegistry->getById($customerId);
            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            $outletId = $customer->getCustomAttribute('outlet_id')->getValue();

            $encryptData = $this->helperData->saveEncryptUrl($outletId, "forgot_set_pinpassword");
            if ($encryptData != '') {
            $url = $this->customerHelper->getForgotPasswordPinUrl();
            $passwordResetLink = $url.'&id='.$encryptData;
            if ($customer->getCustomAttribute('is_migrated')
                && $customer->getCustomAttribute('is_migrated')->getValue() == 1) {
                if (!$customer->getCustomAttribute('outlet_pin')
                    || $customer->getCustomAttribute('outlet_pin')->getValue() == "") {
                    $passwordResetLink .= "&ism=1";
                }
            }

            $this->kakaoSms->sendSms(
                $mobileNumber,
                ['resetpasswordpin_link' => $passwordResetLink],
                'ForgotPassword_001'
             );
            $this->messageManager->addSuccess(__(
                "Kakao Message Sent Successfully"
            ));
            }

            return $resultRedirect->setPath('customer/index/edit/id/' . $customerId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addNotice(__("Something wrong, please try again."));
        }
        return $resultRedirect;
    }
}
