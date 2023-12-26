<?php
namespace Bat\Rma\Block\Adminhtml\CreateReturns\Renderer;

use Bat\Sales\Helper\Data as SalesHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\Framework\App\Request\DataPersistorInterface;

class ProductReturn extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $getDataPersistor;

    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param SalesHelper $salesHelper
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        DataPersistorInterface $dataPersistor,
        SalesHelper $salesHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->getDataPersistor = $dataPersistor;
        $this->salesHelper = $salesHelper;
    }

    /**
     * Get ElementHtml
     */
    public function getElementHtml()
    {
        $id = $this->getDataPersistor->get('id');
        $product = $this->getSkuItems();

        $html = '<div id="return-productId"><table class="data-grid">
        <thead>
            <th class="data-grid-th col-period no-link col-period">Product Name</th>
            <th class="data-grid-th col-period no-link col-period">Product Sku</th>
            <th class="data-grid-th col-period no-link col-period">Qty</th>';
        $html .= '</thead>
        <tbody>';
        foreach ($product as $products) {
            $html .= '<tr><td class="col-period">'.$products->getName().'</td>
                <td class="col-period">'.$products->getSku().'</td>
                <td class="col-period"><input type="number" min="1"
                name="returnQty['.$products->getId().']"/></td></tr>';
        }

        $html .= '</tbody>
        </table></div>';
        return $html;
    }

    /**
     * Get Price Tag items
     *
     * @param int $customerId
     * @return array
     */
    public function getSkuItems()
    {
        return $this->salesHelper->getIroProductList();
    }
}
