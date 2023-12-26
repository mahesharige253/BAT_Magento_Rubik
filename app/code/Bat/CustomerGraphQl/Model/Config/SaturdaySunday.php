<?php

namespace Bat\CustomerGraphQl\Model\Config;

use Magento\Framework\Option\ArrayInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

class SaturdaySunday implements ArrayInterface
{
    public function toOptionArray()
    {

        $options = [];
        $options[] = [
            'value' => 'Saturday',
            'label' => __('Saturday'),
        ];
        $options[] = [
            'value' => 'Sunday',
            'label' => __('Sunday'),
        ];

        return $options;
    }
}
