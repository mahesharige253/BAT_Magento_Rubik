<?php
namespace Bat\Discount\Model\Source;
 
use Magento\Framework\Data\OptionSourceInterface;
 
class DiscountRuleType implements OptionSourceInterface
{
    /**
     * To Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['label' => __('--Select Type--'), 'value' => ''],
            ['label' => __('Special Customer'), 'value' => 1],
            ['label' => __('First Order'), 'value' => 2],
            ['label' => __('Volume'), 'value' => 3],
            ['label' => __('SKU/NPI'), 'value' => 4]
        ];
       
        return $options;
    }
}
