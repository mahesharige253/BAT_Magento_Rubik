<?php

namespace Bat\SalesGraphQl\Model;

use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Customer\Model\CustomerFactory;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Customer\Helper\Data as CustomerHelper;
use Bat\JokerOrder\Model\JokerOrderCancellation;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;

class CancelOrderModel extends AbstractModel
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagementInterface;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory; 

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var CustomerHelper
     */
    private CustomerHelper $helperData;

     /**
     * @var JokerOrderCancellation
     */
    protected $jokerOrderCancellation;

      /**
     * @var GetDiscountMessage
     */
    protected $getDiscountMessage;

    /**
     * @param OrderFactory $orderFactory
     * @param OrderManagementInterface $orderManagementInterface
     * @param KakaoSms $kakaoSms
     * @param CustomerHelper $helperData
     * @param JokerOrderCancellation $jokerOrderCancellation
     * @param GetDiscountMessage $getDiscountMessage
     */
    public function __construct(
        OrderFactory $orderFactory,
        OrderManagementInterface $orderManagementInterface,
        CustomerFactory $customerFactory,
        KakaoSms $kakaoSms,
        CustomerHelper $helperData,
        JokerOrderCancellation $jokerOrderCancellation,
        GetDiscountMessage $getDiscountMessage
    ) {
        $this->orderFactory = $orderFactory;
        $this->orderManagementInterface = $orderManagementInterface;
        $this->customerFactory = $customerFactory;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->jokerOrderCancellation = $jokerOrderCancellation;
        $this->getDiscountMessage = $getDiscountMessage;
    }

    /**
     * Order Cancel by increment Id
     *
     * @param type $incrementId
     * @return type array
     */
    public function orderCancelByIncrementId($incrementId, $customerId)
    {
        $response = ['success' => false, 'message' => ''];
        try {
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            $customer = $this->customerFactory->create()->load($customerId);
            if(($customerId == $order->getCustomerId()) || $order->getParentOutletId() == $customer->getOutletId()) {
                if ($order->canCancel()) {
                    $order->cancel()->save();
                    if($order['entity_id']) {
                        $customerId = $order['customer_id'];
                        $customer = $this->helperData->getCustomerById($customerId); 
                        if($customer->getCustomAttribute('mobilenumber')) {
                            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                            /* Kakao SMS for cancel order by customer */
                            $this->kakaoSms->sendSms($mobileNumber, [], 'CustomerCancel_001');
                        }
                    }
                    $response['success'] = true;
                    $response['message'] = __('You canceled the order successfully');
                    $this->jokerOrderCancellation->returnJokerOrder($customerId,$incrementId);
                    if($order->getDiscountAmount() != '' && $order->getAppliedRuleIds() != ''){
                        $this->getDiscountMessage->setCustomerTimesUsed($customerId, $order->getAppliedRuleIds());
                    }
                } else {
                    $response['message'] = __('This order can not be canceled');
                }
            } else {
                $response['message'] = __('Invalid order ID');
            }
            return $response;
        } catch (\Exception $e) {
            return $response;
        }
    }
}
