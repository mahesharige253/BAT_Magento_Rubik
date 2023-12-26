<?php

namespace Bat\Sales\Model;

use Magento\Rma\Block\Adminhtml\Order\View\Buttons;

/**
 * @class RemoveCreateReturnButton
 * Remove create returns button conditionally
 */
class RemoveCreateReturnButton extends Buttons
{
    /**
     * Check if 'Create RMA' button has to be displayed
     *
     * @return boolean
     */
    protected function _isCreateRmaButtonRequired()
    {
        $parentBlock = $this->getParentBlock();
        return false;
    }
}
