<?php
namespace Bat\Rma\Block\Adminhtml\CreateReturns\Edit;

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
        $this->setTitle(__('Create New Returns'));
    }
}
