<?php
namespace Bat\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\RequestInterface;

class ValidateDayTimeOrderFrequency implements ObserverInterface
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Customer before save validation
     *
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $postData = $this->request->getPost();
        $data = $postData['customer'];
        if (in_array($data['approval_status'], ['2', '5'])) {
            return;
        }

        if ($data['order_frequency_week'] == ''
            || $data['order_frequency_month'] == ''
            || $data['order_frequency_day'] == ''
            || $data['bat_order_frequency'] == ''
            || $data['order_frequency_time_to'] == ''
            || $data['order_frequency_time_from'] == '') {
            throw new LocalizedException(
                __('Plesae select frequency information')
            );
        }

        $this->validateDayTime($data);
        if ($data['bat_order_frequency'] == 0) {
            $this->validateForWeekly($data);
        }
        if ($data['bat_order_frequency'] == 1) {
            $this->validateForBiWeekly($data);
        }
        if ($data['bat_order_frequency'] == 2) {
            $this->validateForMonthly($data);
        }
        if ($data['bat_order_frequency'] == 3) {
            $this->validateForBiMonthly($data);
        }
    }

    /**
     * Validate For Bi-Monthly
     *
     * @param array $data
     * @throws LocalizedException
     */
    public function validateForBiMonthly($data)
    {
        $week = ['week_one', 'week_two', 'week_three', 'week_four', 'week_five'];
        $month = ['odd', 'even'];
        if (!in_array($data['order_frequency_week'], $week) || !in_array($data['order_frequency_month'], $month)) {
            $this->throwException('Bi-Monthly');
        }
    }

    /**
     * Validate For Monthly
     *
     * @param array $data
     * @throws LocalizedException
     */
    public function validateForMonthly($data)
    {
        $week = ['week_one', 'week_two', 'week_three', 'week_four', 'week_five'];
        if (!in_array($data['order_frequency_week'], $week) || $data['order_frequency_month'] !='every') {
            $this->throwException('Monthly');
        }
    }

     /**
      * Validate For Bi-Weekly
      *
      * @param array $data
      * @throws LocalizedException
      */
    public function validateForBiWeekly($data)
    {
        $week = ['odd', 'even'];
        if (!in_array($data['order_frequency_week'], $week) || $data['order_frequency_month'] !='every') {
            $this->throwException('Bi-Weekly');
        }
    }

    /**
     * Validate For Weekly
     *
     * @param array $data
     * @throws LocalizedException
     */
    public function validateForWeekly($data)
    {
        if ($data['order_frequency_week'] != 'every' || $data['order_frequency_month'] !='every') {
            $this->throwException('Weekly');
        }
    }

    /**
     * Validate Day Time
     *
     * @param array $data
     * @throws LocalizedException
     */
    public function validateDayTime($data)
    {
        $fromTime = date("H:i:s", strtotime($data['order_frequency_time_from']));
        $toTime = date("H:i:s", strtotime($data['order_frequency_time_to']));
        if ($fromTime >= $toTime) {
            throw new LocalizedException(
                __('Order frequency time from and to less or equal time not allowed')
            );
        }
    }

    /**
     * Throw Exception
     *
     * @param string $frequency
     * @throws LocalizedException
     */
    private function throwException($frequency)
    {
        throw new LocalizedException(
            __(
                'Please select Order Frequency Month and Order Frequency Week for '.$frequency.' Order frequency.'
            )
        );
    }
}
