<?php
namespace Bat\Customer\Model\Entity\Attribute\Source;

use Bat\CustomerGraphQl\Helper\Data;

class OrderFrequencyDayOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $allowWeekend = $this->helper->getAllowSaturdaySunday();
        $this->_options = [
            ['label' => __('--Select Day--'), 'value' => ''],
            ['label' => __('Monday'), 'value' => 'Monday'],
            ['label' => __('Tuesday'), 'value' => 'Tuesday'],
            ['label' => __('Wednesday'), 'value' => 'Wednesday'],
            ['label' => __('Thursday'), 'value' => 'Thursday'],
            ['label' => __('Friday'), 'value' => 'Friday']
        ];
        
        if ($allowWeekend) {
            $allowWeekend = explode(',', $allowWeekend);
            $weekendDay = [];
            foreach ($allowWeekend as $day) {
                $weekendDay[] = ['label' => __($day), 'value' => $day];
            }
            $this->_options = array_merge($this->_options, $weekendDay);
        }
        
        return $this->_options;
    }
}
