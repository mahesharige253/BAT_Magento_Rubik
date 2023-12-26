<?php
namespace Bat\Customer\Model\Entity\Attribute\Source;

class OrderFrequencyTimeOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [
            ['label' => __('--Select Time--'), 'value' => ''],
            ['label' => __('12:00 AM'), 'value' => '12am'],
            ['label' => __('01:00 AM'), 'value' => '01am'],
            ['label' => __('02:00 AM'), 'value' => '02am'],
            ['label' => __('03:00 AM'), 'value' => '03am'],
            ['label' => __('04:00 AM'), 'value' => '04am'],
            ['label' => __('05:00 AM'), 'value' => '05am'],
            ['label' => __('06:00 AM'), 'value' => '06am'],
            ['label' => __('07:00 AM'), 'value' => '07am'],
            ['label' => __('08:00 AM'), 'value' => '08am'],
            ['label' => __('09:00 AM'), 'value' => '09am'],
            ['label' => __('10:00 AM'), 'value' => '10am'],
            ['label' => __('11:00 AM'), 'value' => '11am'],
            ['label' => __('12:00 PM'), 'value' => '12pm'],
            ['label' => __('01:00 PM'), 'value' => '01pm'],
            ['label' => __('02:00 PM'), 'value' => '02pm'],
            ['label' => __('03:00 PM'), 'value' => '03pm'],
            ['label' => __('04:00 PM'), 'value' => '04pm'],
            ['label' => __('05:00 PM'), 'value' => '05pm'],
            ['label' => __('06:00 PM'), 'value' => '06pm'],
            ['label' => __('07:00 PM'), 'value' => '07pm'],
            ['label' => __('08:00 PM'), 'value' => '08pm'],
            ['label' => __('09:00 PM'), 'value' => '09pm'],
            ['label' => __('10:00 PM'), 'value' => '10pm'],
            ['label' => __('11:00 PM'), 'value' => '11pm'],
            ['label' => __('11:59:59 PM'), 'value' => '11:59:59pm']
            
        ];
        return $this->_options;
    }
}
