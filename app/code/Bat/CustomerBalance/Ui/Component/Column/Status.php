<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\CustomerBalance\Ui\Component\Column;

/**
 * @class Status
 * Returns option array for status
 */
class Status implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return statuses option array
     *
     * @return array
     */
    public function toOptionArray()
    {

        return [1 => __('Yes'), 0 => __('No')];
    }
}
