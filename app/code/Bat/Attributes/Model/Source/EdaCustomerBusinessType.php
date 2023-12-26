<?php

namespace Bat\Attributes\Model\Source;

class EdaCustomerBusinessType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Select Business Licence Type'), 'value' => ''],
                ['label' => __('도소매'), 'value' => '01'],
                ['label' => __('소매'), 'value' => '02'],
                ['label' => __('식품'), 'value' => '03'],
                ['label' => __('서비스'), 'value' => '04'],
                ['label' => __('부동산'), 'value' => '05'],
                ['label' => __('대여'), 'value' => '06'],
                ['label' => __('대중음식'), 'value' => '07'],
                ['label' => __('음식,숙박'), 'value' => '08'],
                ['label' => __('잡화'), 'value' => '09'],
                ['label' => __('식품잡화'), 'value' => '10'],
                ['label' => __('제조,소매'), 'value' => '11'],
                ['label' => __('제조,서비스'), 'value' => '12'],
                ['label' => __('편의점'), 'value' => '13'],
                ['label' => __('호텔'), 'value' => '14'],
                ['label' => __('도매업'), 'value' => '15'],
                ['label' => __('음식'), 'value' => '16'],
                ['label' => __('기타'), 'value' => '99'],
            ];
        }
        return $this->_options;
    }
}
