<?php
namespace Bat\AccountClosure\Block\Adminhtml\Accountclosure\Edit\Tab;

use Magento\Framework\Data\Form\Element\Date as DateElement;

class Date extends DateElement
{
    /**
     * Get the HTML for the element
     *
     * @return string
     */
    public function getElementHtml()
    {
        $this->addClass('admin__control-text');
        $this->setDateFormat('yyyy-MM-dd');
        $this->setShowsTime(true);
        $this->setCanReadonly(true);
        $this->setMinDate('0');

        return parent::getElementHtml();
    }
}
