<?php
namespace Bat\AccountClosure\Block\Adminhtml\Accountclosure\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Construct
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('page_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Account Closure Request'));
    }
}
