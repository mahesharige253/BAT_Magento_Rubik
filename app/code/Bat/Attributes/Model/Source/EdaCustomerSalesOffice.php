<?php

namespace Bat\Attributes\Model\Source;

class EdaCustomerSalesOffice extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Select Sales Office'), 'value' => ''],
                ['label' => __('Asan'), 'value' => 'AS'],
                ['label' => __('Kyeonggi North'), 'value' => 'BB'],
                ['label' => __('Cheongju'), 'value' => 'CG'],
                ['label' => __('Jeju'), 'value' => 'CJ'],
                ['label' => __('Gunpo'), 'value' => 'GP'],
                ['label' => __('Incheon'), 'value' => 'IC'],
                ['label' => __('Jeonju'), 'value' => 'JJ'],
                ['label' => __('Gangbuk'), 'value' => 'KB'],
                ['label' => __('Gwangju'), 'value' => 'KJ'],
                ['label' => __('Gangneung'), 'value' => 'KL'],
                ['label' => __('Gangnam'), 'value' => 'KN'],
                ['label' => __('Masan'), 'value' => 'MS'],
                ['label' => __('Osan'), 'value' => 'OS'],
                ['label' => __('Busan East'), 'value' => 'PB'],
                ['label' => __('Daegu'), 'value' => 'TG'],
                ['label' => __('Daejeon'), 'value' => 'TJ'],
                ['label' => __('Ulsan'), 'value' => 'US'],
                ['label' => __('Wonju'), 'value' => 'WJ']
            ];
        }
        return $this->_options;
    }
}
