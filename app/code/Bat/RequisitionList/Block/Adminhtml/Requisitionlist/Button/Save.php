<?php

namespace Bat\RequisitionList\Block\Adminhtml\Requisitionlist\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * @class Save
 * Save Button for UI Component
 */
class Save extends Generic implements ButtonProviderInterface
{

    /**
     * Add save Button
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90
        ];
    }

    /**
     * Return Save url
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', []);
    }
}
