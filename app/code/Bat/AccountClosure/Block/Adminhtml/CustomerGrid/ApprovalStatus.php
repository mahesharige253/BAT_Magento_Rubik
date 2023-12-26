<?php

namespace Bat\AccountClosure\Block\Adminhtml\CustomerGrid;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;

/**
 * Adminhtml newsletter queue grid block status item renderer
 */
class ApprovalStatus extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @param Config $eavConfig
     */
    public function __construct(
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * Render ApprovalStatus Label
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Framework\Phrase
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $statusLabel = $this->getStatus($row->getApprovalStatus());
        return __($statusLabel->getText());
    }

    /**
     * Function to get label
     *
     * @param string $status
     * @return \Magento\Framework\Phrase
     */
    public function getStatus($status)
    {
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'approval_status');

        if ($attribute->usesSource()) {
            $options = $attribute->getSource()->getAllOptions();

            foreach ($options as $option) {
                if ($option['value'] == $status) {
                    return $option['label'];
                }
            }
        }
        
        return __('Unknown');
    }
}
