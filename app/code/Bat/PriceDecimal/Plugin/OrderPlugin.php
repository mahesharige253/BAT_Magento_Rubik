<?php

declare(strict_types=1);

namespace Bat\PriceDecimal\Plugin;

class OrderPlugin
{
    /**
     * Before Format Price Precision
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param array $args
     * @return array
     */
    public function beforeFormatPricePrecision(
        \Magento\Sales\Model\Order $subject,
        ...$args
    ) {
        $args[1] = 0;
        return $args;
    }
}