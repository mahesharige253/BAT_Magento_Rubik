<?php

namespace Bat\AccountClosure\Block\Adminhtml\CustomerGrid;

use Magento\Customer\Model\Customer;
use Bat\Customer\Helper\Data;

/**
 * Adminhtml newsletter queue grid block status item renderer
 */
class OutletName extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var Data
     */
    protected Data $helperData;

    /**
     * @param Data $helperData
     */
    public function __construct(
        Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * Render Outlet Name
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Framework\Phrase
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $outletName = $this->helperData->getInfo($row->getEntity_id());
        return __($outletName);
    }
}
