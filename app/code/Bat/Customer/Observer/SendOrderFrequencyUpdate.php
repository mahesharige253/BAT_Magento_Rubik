<?php
namespace Bat\Customer\Observer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Bat\Kakao\Model\Sms as KakaoSms;

/**
 * @class SendOrderFrequencyUpdate
 */
class SendOrderFrequencyUpdate implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @param LoggerInterface $logger
     * @param KakaoSms $kakaoSms
     */
    public function __construct(
        LoggerInterface $logger,
        KakaoSms $kakaoSms
    ) {
        $this->logger = $logger;
        $this->kakaoSms = $kakaoSms;
    }

    /**
     * Create/Update customers to EDA table
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        try {
            /** @var CustomerInterface $customer */
            $customer = $observer->getCustomerDataObject();

            /** @var CustomerInterface $customerOriginalData */
            $originalData = $observer->getOrigCustomerDataObject();
            $updateOrderFrequency = false;
            if (isset($originalData)) {
                if ($originalData->getCustomAttribute('bat_order_frequency')) {
                    $batOrderFrequencyPrevious = $originalData->getCustomAttribute('bat_order_frequency')->getValue();
                    $batOrderFrequencyCurrent = $customer->getCustomAttribute('bat_order_frequency')->getValue();
                    if ($batOrderFrequencyPrevious != $batOrderFrequencyCurrent) {
                        $updateOrderFrequency = true;
                    }
                }

                if ($originalData->getCustomAttribute('order_frequency_day') &&
                    !empty($customer->getCustomAttribute('order_frequency_day'))) {
                    $frequencyDayPrevious = $originalData->getCustomAttribute('order_frequency_day')->getValue();
                    $frequencyDayCurrent = $customer->getCustomAttribute('order_frequency_day')->getValue();
                    if ($frequencyDayPrevious != $frequencyDayCurrent) {
                        $updateOrderFrequency = true;
                    }
                }

                if ($originalData->getCustomAttribute('order_frequency_time_to') &&
                    !empty($customer->getCustomAttribute('order_frequency_time_to'))) {
                    $frequencyTimeToPrevious = $originalData->getCustomAttribute('order_frequency_time_to')->getValue();
                    $frequencyTimeToCurrent = $customer->getCustomAttribute('order_frequency_time_to')->getValue();
                    if ($frequencyTimeToPrevious != $frequencyTimeToCurrent) {
                        $updateOrderFrequency = true;
                    }
                }

                if ($originalData->getCustomAttribute('order_frequency_time_from') &&
                    !empty($customer->getCustomAttribute('order_frequency_time_from'))) {
                    $frequencyTimeFromPrevious = $originalData->getCustomAttribute('order_frequency_time_from')->getValue();
                    $frequencyTimeFromCurrent = $customer->getCustomAttribute('order_frequency_time_from')->getValue();
                    if ($frequencyTimeFromPrevious != $frequencyTimeFromCurrent) {
                        $updateOrderFrequency = true;
                    }
                }
            }
            /* Kakao Message to customer for notifying order frequency day */
            if ($customer->getCustomAttribute('approval_status') != '') {
                if ($customer->getCustomAttribute('approval_status')->getValue() == 1) {
                    if ($updateOrderFrequency) {
                        if ($customer->getCustomAttribute('mobilenumber')) {
                            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                            $this->kakaoSms->sendSms($mobileNumber, [], 'FrequencyChange_001');
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->info('Order frequency update failed '.$e->getMessage());
        }
        return $this;
    }
}
