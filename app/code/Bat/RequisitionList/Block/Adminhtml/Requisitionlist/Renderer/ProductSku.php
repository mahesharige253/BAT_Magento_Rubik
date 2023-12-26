<?php
namespace Bat\RequisitionList\Block\Adminhtml\Requisitionlist\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

class ProductSku extends AbstractRenderer
{
    /**
     * Render
     *
     * @param object $row
     * @return html
     */
    public function render(DataObject $row)
    {
      
        $entityId = $row->getData($this->getColumn()->getIndex());
        $sku = $row->getData($this->getColumn()->getSku());
        // Compose html
        $html = $sku.'<input type="hidden" ';
        $html .= 'name="sku['.$entityId.']" ';
        $html .= 'value="' . $sku . '"';
        $html .= 'class="input-text admin__control-text '
                . $this->getColumn()->getInlineCss() . '" />';
        return $html;
    }
}
