<?php

namespace Bat\OrderProducts\Model;

use Magento\Sales\Model\OrderFactory;
use Bat\OrderProducts\Helper\Data;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Bat\SalesGraphQl\Model\OrderPaymentDeadline;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Customer\Helper\Data as CustomerHelper;
use Bat\JokerOrder\Model\JokerOrderCancellation;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;

/**
 * @class OrderCancelMessage
 * Order cancel by cron
 */
class OrderCancelMessage
{
    private const CANCEL_ORDER_CRON_ENABLE = 'payment_deadline/bat_order_cancel/sales_order_cron_enable';
    private const LOG_ENABLED_PATH = 'payment_deadline/bat_order_cancel/sales_order_cron_log_enable';

    /**
     * @var boolean
     */
    private $logEnabled;

     /**
      * @var OrderFactory
      */
    protected $orderFactory;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var OrderPaymentDeadline
     */
    protected $_orderCollectionFactory;

    /**
     * @var OrderPaymentDeadline
     */
    protected $orderPaymentDeadline;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

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
    private $jokerOrderCancellation;

      /**
     * @var GetDiscountMessage
     */
    protected $getDiscountMessage;

    /**
     * @param OrderFactory $orderFactory
     * @param Data $dataHelper
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderPaymentDeadline $orderPaymentDeadline
     * @param TimezoneInterface $timezoneInterface
     * @param KakaoSms $kakaoSms
     * @param CustomerHelper $helperData
     * @param JokerOrderCancellation $jokerOrderCancellation
     * @param GetDiscountMessage $getDiscountMessage
     */
    public function __construct(
        OrderFactory $orderFactory,
        Data $dataHelper,
        CollectionFactory $orderCollectionFactory,
        OrderPaymentDeadline $orderPaymentDeadline,
        TimezoneInterface $timezoneInterface,
        KakaoSms $kakaoSms,
        CustomerHelper $helperData,
        JokerOrderCancellation $jokerOrderCancellation,
        GetDiscountMessage $getDiscountMessage

    ) {
        $this->orderFactory = $orderFactory;
        $this->dataHelper = $dataHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderPaymentDeadline = $orderPaymentDeadline;
        $this->timezoneInterface = $timezoneInterface;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->jokerOrderCancellation = $jokerOrderCancellation;
        $this->getDiscountMessage = $getDiscountMessage;
    }

    /**
     * Check order payment deadline
     */
    public function cancelOrder()
    {
        $isCronEnable = $this->dataHelper->getConfig(self::CANCEL_ORDER_CRON_ENABLE);
        $isLogEnabled = $this->dataHelper->getConfig(self::LOG_ENABLED_PATH);

        if ($isCronEnable == 1) {
            $this->dataHelper->logOrderCancel(__('------Cancel order cron Started-----------'));
            try {
                $currentDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
                $collection = $this->_orderCollectionFactory->create()
                 ->addAttributeToSelect('entity_id')
                 ->addAttributeToSelect('customer_id')
                 ->addAttributeToSelect('increment_id')
                 ->addAttributeToFilter('status', 'pending')
                 ->setOrder('created_at', 'desc');

                if ($collection->count()) {
                    foreach ($collection as $key => $orderCollection) {
                        $orderPaymentDeadline = $this->orderPaymentDeadline->
                        getPaymentDeadline($orderCollection['entity_id']);
                        $orderPaymentDeadline = date('Y-m-d 23:0:0', strtotime($orderPaymentDeadline));
                        if ($orderPaymentDeadline < $currentDate) {
                            $this->getCancelOrder($orderCollection['increment_id']);
                            $this->sendSmsToCustomer($orderCollection);
                            $this->dataHelper->logOrderCancel('Order Number => '.$orderCollection['increment_id']);
                        }
                    }
                } else {
                    if ($isLogEnabled) {
                        $this->dataHelper->logOrderCancel('No orders to cancel');
                    }
                }
            } catch (\Exception $e) {
                if ($isLogEnabled) {
                    $this->dataHelper->logOrderCancel($e->getMessage());
                }
            }
            $this->dataHelper->logOrderCancel(__('------Cancel order cron Completed-----------'));
        }
    }

    /**
     * Cancel order
     *
     * @param string $orderIncrementId
     * @return null
     */
    public function getCancelOrder($orderIncrementId)
    {
        try {
            $isLogEnabled = $this->dataHelper->getConfig(self::LOG_ENABLED_PATH);
            $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
            $customerId = $order->getCustomerId();
            if ($order->canCancel()) {
                $order->cancel()->save();
                if ($isLogEnabled) {
                    $this->dataHelper->logOrderCancel(
                        __('Order '.$orderIncrementId.' has been canceled')
                    );
                $this->jokerOrderCancellation->returnJokerOrder($customerId,$orderIncrementId);
                }
            }
            if($order->getDiscountAmount() != '' && $order->getAppliedRuleIds() != ''){
                $this->getDiscountMessage->setCustomerTimesUsed($customerId, $order->getAppliedRuleIds());
            }
        } catch (\Exception $e) {
            if ($isLogEnabled) {
                $this->dataHelper->logOrderCancel($e->getMessage());
            }
        }
    }

    /**
     * Send SMS to customer for order cancel
     *
     * @param array $orderCollection
     * @return null
     */
    public function sendSmsToCustomer($orderCollection)
    {
        if ($orderCollection['entity_id']) {
            $customerId = $orderCollection['customer_id'];
            $customer = $this->helperData->getCustomerById($customerId);
            if ($customer->getCustomAttribute('mobilenumber')) {
                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                /* Kakao SMS for cancel order by cron */
                $this->kakaoSms->sendSms($mobileNumber, [], 'UnpaidCancel_001');
            }
        }
    }
}
