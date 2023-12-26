<?php

namespace Bat\Rma\Block\Adminhtml\CreateReturns\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * @class Add
 * Save Button for UI Component
 */
class Add extends Generic implements ButtonProviderInterface
{

    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData()
    {

        return [
            'label' => __('Create New Returns'),
            'on_click' => sprintf("location.href = '%s';", $this->getAddUrl()),
            'class' => 'add primary',
            'sort_order' => 10,
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getAddUrl()
    {
        return $this->getUrl('*/*/searchoutlet');
    }
}
