<?php

namespace Bat\Rma\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ReturnSwiftCode
 *
 * Return Swift codes option array
 */
class ReturnSwiftCode implements ArrayInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Select Reason')],
            ['value' => '11', 'label' => __('배송중 파손')],
            ['value' => '12', 'label' => __('제품 불량')],
            ['value' => '13', 'label' => __('오배송')],
            ['value' => '14', 'label' => __('배송 실패')],
            ['value' => '15', 'label' => __('고객 변심_Hypercare')],
            ['value' => '16', 'label' => __('Slow Moving 제품 반품')],
            ['value' => '17', 'label' => __('A/R Trouble 제품 반품')],
            ['value' => '18', 'label' => __('Special 반품')],
            ['value' => '19', 'label' => __('입점유지')],
            ['value' => '20', 'label' => __('old 반품')],
            ['value' => '21', 'label' => __('파손 반품')],
        ];
    }

    /**
     * Return Swift codes
     *
     * @return array
     */
    public function getReturnSwiftCodeLabel()
    {
        $options = [];
        $swiftCodes = $this->toOptionArray();
        $swiftCodes[] = ['value' => '10', 'label' => __('폐업반품')];
        foreach ($swiftCodes as $value) {
            $options[$value['value']] = (string)$value['label'];
        }
        return $options;
    }
}
