<?php

namespace Bat\OrderProducts\Model;

use Magento\Sales\Model\OrderFactory;
use Bat\OrderProducts\Helper\Data;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Bat\SalesGraphQl\Model\OrderPaymentDeadline;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Customer\Helper\Data as CustomerHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\Currency\Data\Currency as CurrencyData;

/**
 * @class CreateOrderEda
 * Cron to create orders in EDA
 */
class PaymentReminderMessage
{
    private const PAYMENT_ORDER_REMINDER_CRON_ENABLE =
    'payment_deadline/bat_order_payment_reminder/order_payment_reminder_cron_enable';
    private const LOG_ENABLED_PATH =
    'payment_deadline/bat_order_payment_reminder/order_payment_reminder_cron_log_enable';

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
     * @var Currency
     */ 
    protected Currency $currency;

    /**
     * @param OrderFactory $orderFactory
     * @param Data $dataHelper
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderPaymentDeadline $orderPaymentDeadline
     * @param TimezoneInterface $timezoneInterface
     * @param KakaoSms $kakaoSms
     * @param CustomerHelper $helperData
     * @param Currency $currency
     */
    public function __construct(
        OrderFactory $orderFactory,
        Data $dataHelper,
        CollectionFactory $orderCollectionFactory,
        OrderPaymentDeadline $orderPaymentDeadline,
        TimezoneInterface $timezoneInterface,
        KakaoSms $kakaoSms,
        CustomerHelper $helperData,
        Currency $currency
    ) {
        $this->orderFactory = $orderFactory;
        $this->dataHelper = $dataHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderPaymentDeadline = $orderPaymentDeadline;
        $this->timezoneInterface = $timezoneInterface;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->currency = $currency;
    }

    /**
     * Check order payment reminder
     */
    public function sendPaymentReminderMessage()
    {
        $isCronEnable = $this->dataHelper->getConfig(self::PAYMENT_ORDER_REMINDER_CRON_ENABLE);
        $isLogEnabled = $this->dataHelper->getConfig(self::LOG_ENABLED_PATH);

        if ($isCronEnable == 1) {
            $this->dataHelper->logPaymentReminder(__('-----Order payment reminder cron started-------'));
            try {
                $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
                $collection = $this->_orderCollectionFactory->create()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToSelect('customer_id')
                ->addAttributeToSelect('increment_id')
                ->addAttributeToSelect('grand_total')
                ->addAttributeToFilter('status', 'pending')
                ->setOrder('created_at', 'desc');

                if ($collection->count()) {
                    foreach ($collection as $key => $orderCollection) {
                        $orderPaymentDeadline = $this->orderPaymentDeadline->
                        getPaymentDeadline($orderCollection['entity_id']);
                        $orderPaymentDeadline = date('Y-m-d', strtotime($orderPaymentDeadline));

                        if ($orderPaymentDeadline == $currentDate) {
                            $this->sendSmsToCustomer($orderCollection);
                        }
                    }
                } else {
                    if ($isLogEnabled) {
                        $this->dataHelper->logPaymentReminder('No order exist to remind');
                    }
                }
            } catch (\Exception $e) {
                if ($isLogEnabled) {
                    $this->dataHelper->logPaymentReminder($e->getMessage());
                }
            }
            $this->dataHelper->logPaymentReminder(__('------Order payment reminder cron Completed-----------'));
        }
    }

    /**
     * Send SMS to customer for order payment reminder
     *
     * @param Array $orderCollection
     * @return $this|void
     */
    public function sendSmsToCustomer($orderCollection)
    {
        if ($orderCollection['entity_id']) {
            $customerId = $orderCollection['customer_id'];
            $customer = $this->helperData->getCustomerById($customerId);
            if ($customer->getCustomAttribute('mobilenumber')) {
                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();

                 /* Kakao SMS for order payment reminder by cron */
                $vbaBankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
                $vbaBankNumber = $customer->getCustomAttribute('virtual_account')->getValue();
                $vbaBankName = $this->helperData->getAttributeLabelByValue('virtual_bank', $vbaBankCode);
                $vbaBankInfo = $vbaBankName.', '.$vbaBankNumber;

                $params = [
                    'salesorder_number' => $orderCollection['increment_id'],
                    'totalsalesorder_amount' => $this->currency->format($orderCollection['grand_total'], ['display'=> CurrencyData::NO_SYMBOL, 'precision' => 0], false),
                    'vbabank_vbanumber' => $vbaBankInfo
                ];
                $this->kakaoSms->sendSms($mobileNumber, $params, 'PaymentRequest_001');
                $this->dataHelper->logPaymentReminder($orderCollection['increment_id']);
            }
        }
    }
}
