<?php

namespace Bat\Attributes\Model\Source;

class EdaCustomerDeliveryPlant extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @inheritdoc
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Select Delivery Plant'), 'value' => ''],
                ['label' => __('Asan'), 'value' => 'AS00'],
                ['label' => __('Hong Sung'), 'value' => 'AS01'],
                ['label' => __('Kyunggi North'), 'value' => 'BB00'],
                ['label' => __('Cheongju'), 'value' => 'CG00'],
                ['label' => __('Cheju'), 'value' => 'CJ00'],
                ['label' => __('Gunpo'), 'value' => 'GP00'],
                ['label' => __('Incheon'), 'value' => 'IC00'],
                ['label' => __('Jeonju'), 'value' => 'JJ00'],
                ['label' => __('Goyang'), 'value' => 'KB00'],
                ['label' => __('Gwangju'), 'value' => 'KJ00'],
                ['label' => __('Soon Cheon'), 'value' => 'KJ01'],
                ['label' => __('Mok Po'), 'value' => 'KJ02'],
                ['label' => __('Gangneung'), 'value' => 'KL00'],
                ['label' => __('Hanam'), 'value' => 'KN00'],
                ['label' => __('Masan'), 'value' => 'MS00'],
                ['label' => __('Osan'), 'value' => 'OS00'],
                ['label' => __('Pusan East'), 'value' => 'PB00'],
                ['label' => __('Daegu'), 'value' => 'TG00'],
                ['label' => __('An Dong'), 'value' => 'TG01'],
                ['label' => __('Daejeon'), 'value' => 'TJ00'],
                ['label' => __('Ulsan'), 'value' => 'US00'],
                ['label' => __('Po Hang'), 'value' => 'US01'],
                ['label' => __('Wonju'), 'value' => 'WJ00']
            ];
        }
        return $this->_options;
    }
}
