<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\CustomerBalance\Model;

/**
 * @class Balance
 * Customer balance model to allow negative values
 */
class Balance extends \Magento\CustomerBalance\Model\Balance
{
    /**
     * Validate & adjust amount change
     *
     * @return float
     */
    protected function _prepareAmountDelta()
    {
        $result = 0;
        if ($this->hasAmountDelta()) {
            $result = (double)$this->getAmountDelta();
        }
        $this->setAmountDelta($result);
        if (!$this->getId()) {
            $this->setAmount($result);
        } else {
            $this->setAmount($this->getAmount() + $result);
        }
        return $result;
    }
}
