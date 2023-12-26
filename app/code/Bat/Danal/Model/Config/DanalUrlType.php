<?php

namespace Bat\Danal\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class DanalUrlType implements ArrayInterface
{
    /**
     * Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => 'https://wauth.teledit.com/Danal/WebAuth/Web/Start.php',
                'label' => __('Production Mode'),
            ],
            [
                'value' => '/api/dummydanal',
                'label' => __('Test Mode'),
            ]
        ];
        return $options;
    }
}
