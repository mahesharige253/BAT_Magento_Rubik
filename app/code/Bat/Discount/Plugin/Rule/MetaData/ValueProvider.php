<?php
namespace Bat\Discount\Plugin\Rule\MetaData;

use Bat\Discount\Model\Rule\Action\SpecialCustomerQtyDiscount;
use Magento\SalesRule\Model\Rule\Metadata\ValueProvider as MetaDataValueProvider;

class ValueProvider
{

    /**
     * After Get Meta data Values
     *
     * @param MetaDataValueProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetMetadataValues(
        MetaDataValueProvider $subject,
        $result
    ) {
        $applyOptions = [
            [
                'label' => __('Special Customer Qty Discount'),
                'value' => SpecialCustomerQtyDiscount::ACTION_NAME,
            ],
        ];
        foreach ($applyOptions as $optionArr) {
            array_push($result['actions']['children']['simple_action']
                ['arguments']['data']['config']['options'], $optionArr);
        }
        return $result;
    }
}
