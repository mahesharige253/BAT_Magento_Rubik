<?php
namespace Bat\RequisitionList\Block\Adminhtml\Requisitionlist\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Bat\RequisitionList\Model\RequisitionListItemAdminFactory;

class ProductQty extends AbstractRenderer
{
    /**
     * @var RequisitionListItemAdminFactory
     */
    protected $requisitionListItemFactory;

    /**
     * @param RequisitionListItemAdminFactory $requisitionListItemFactory
     */
    public function __construct(
        RequisitionListItemAdminFactory $requisitionListItemFactory
    ) {
        $this->requisitionListItemFactory = $requisitionListItemFactory;
    }

    /**
     * Render
     *
     * @param object $row
     * @return html
     */
    public function render(DataObject $row)
    {
        $entityId = $row->getData($this->getColumn()->getIndex());
        $requisitionListId = $this->getColumn()->getRequisitionlistId();
        $requisitionListItemFactory = $this->requisitionListItemFactory->create();
        $qty = $requisitionListItemFactory->getQty($requisitionListId, $entityId);
        if (!empty($qty)) {
            $qty = (int)$qty[0];
        } else {
            $qty = '';
        }
        // Compose html
        $html = '<input type="text" ';
        $html .= 'name="qty['.$entityId.']" ';
        $html .= 'class="qty_'.$entityId.'" ';
        $html .= 'value="'.$qty.'" ';
        $html .= 'class="input-text admin__control-text '
                . $this->getColumn()->getInlineCss() . '" />';
        return $html;
    }
}
