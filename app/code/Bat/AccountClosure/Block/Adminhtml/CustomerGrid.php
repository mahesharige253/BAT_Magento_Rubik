<?php

namespace Bat\AccountClosure\Block\Adminhtml;

use Magento\Backend\Block\Widget\Button\SplitButton;
use Bat\AccountClosure\Block\Adminhtml\CustomerGrid\Grid;

class CustomerGrid extends \Magento\Backend\Block\Widget\Container
{
    /**
     * @var string
     */
    protected $_template = 'grid.phtml';

    /**
     * Prepare button and grid
     *
     * @return \Magento\Catalog\Block\Adminhtml\Product
     */
    protected function _prepareLayout()
    {
        $addButtonProps = [
            'id' => 'add_new_grid',
            'label' => __('Add Closure Request'),
            'class' => 'scalable add primary',
            'button_class' => '',
            'onclick' => "setLocation('" . $this->_getCreateUrl() . "')",
            ];
        $this->buttonList->add('add_new', $addButtonProps);
        $this->setChild(
            'grid',
            $this->getLayout()->createBlock(Grid::class, 'adminclosure.admingrid.grid')
        );
        return parent::_prepareLayout();
    }

    /**
     * Create Url
     *
     * @return string
     */
    protected function _getCreateUrl()
    {
        return $this->getUrl('accountclosure/accountclosure/searchoutlet');
    }

    /**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }
}
