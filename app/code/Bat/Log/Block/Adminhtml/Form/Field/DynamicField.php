<?php
declare(strict_types=1);

namespace Bat\Log\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class DynamicField extends AbstractFieldArray
{
    /**
     * Prepare for render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('key', ['label' => __('Header Key')]);
        $this->addColumn('value', ['label' => __('Header Value')]);
        $this->_addAfter = false;
    }
}
