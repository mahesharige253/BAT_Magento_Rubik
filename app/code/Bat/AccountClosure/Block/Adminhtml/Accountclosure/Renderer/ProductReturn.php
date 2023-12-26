<?php
namespace Bat\AccountClosure\Block\Adminhtml\Accountclosure\Renderer;

use Bat\Sales\Helper\Data as SalesHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\AccountClosure\Model\AccountClosureProductReturnFactory;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Bat\Rma\Model\ResourceModel\ZreResource\ZreCollectionFactory;

class ProductReturn extends \Magento\Framework\Data\Form\Element\AbstractElement
{

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var AccountClosureProductReturnFactory
     */
    private AccountClosureProductReturnFactory $accountClosureProductReturn;

    /**
     * @var RmaRepositoryInterface
     */
    private RmaRepositoryInterface $rmaRepository;

    /**
     * @var UrlInterface
     */
    protected $url;

     /**
     * @var Order
     */
    private $order;

    /**
     * @var ZreCollectionFactory
     */
    private ZreCollectionFactory $zreCollectionFactory;

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $getDataPersistor;

    /**
     * @var AccountClosureProductReturnFactory
     */
    private AccountClosureProductReturnFactory $accountClosureProductReturnFactory;

    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param AccountClosureProductReturnFactory $accountClosureProductReturn
     * @param RmaRepositoryInterface $rmaRepository
     * @param UrlInterface $url
     * @param Order $order
     * @param ZreCollectionFactory $zreCollectionFactory
     * @param SalesHelper $salesHelper
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        DataPersistorInterface $dataPersistor,
        AccountClosureProductReturnFactory $accountClosureProductReturn,
        RmaRepositoryInterface $rmaRepository,
        UrlInterface $url,
        Order $order,
        ZreCollectionFactory $zreCollectionFactory,
        SalesHelper $salesHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->getDataPersistor = $dataPersistor;
        $this->accountClosureProductReturnFactory = $accountClosureProductReturn;
        $this->rmaRepository = $rmaRepository;
        $this->url = $url;
        $this->order = $order;
        $this->zreCollectionFactory = $zreCollectionFactory;
        $this->salesHelper = $salesHelper;
    }

    /**
     * Get ElementHtml
     */
    public function getElementHtml()
    {
        $id = $this->getDataPersistor->get('id');
        $orderLink = '';
        $zreOrders = '';
        if ($id) {
            $accountClosureFactory = $this->accountClosureProductReturnFactory->create();
            $productData = $accountClosureFactory->loadByCustomerId($id);
            $productRtnQty = [];
            $orderId = '';
            $reqQty = '';
            foreach ($productData as $data) {
                $key = $data['qty'].'_'.$data['product_id'];
                $productRtnQty[$key] = $data['product_id'];
                $orderId = ($data['returnOrderId'] != null) ?$data['returnOrderId']: '';
            }
            if ($orderId !='' && $orderId != null) {
                $entityId = $accountClosureFactory->getReturnData($orderId);
                if(count($entityId) >0){
                    $reqQty = $this->getRequestQty($entityId[0]);
                }
                $iroOrder = $this->getReturnOrder($orderId);
                $orderLink = $this->getOrderLink($iroOrder->getEntityId());
                $zreOrders = $iroOrder->getReturnOriginalOrderId();
                if($zreOrders != ''){
                    $zreOrders = explode(', ', $zreOrders);
                }
            }
        }
        $product = $this->getSkuItems();
        $html = '<div id="return-productId">';
        if($id && $orderLink !='') {
            $html .= '<div style="padding-bottom:10px">
            <a href='.$orderLink.' target="_blank">IRO Order('.$orderId.' - '.$iroOrder->getStatusLabel().')</a>
            </div>';
        }
        $zreOrdersHtml = '';
        if($id && $zreOrders != ''){
            foreach ($zreOrders as $zreOrder){
                $zre = $this->getReturnOrder($zreOrder);
                $zreLink = $this->getOrderLink($zre->getEntityId());
                $zreOrdersHtml .= '<div style="padding-bottom:10px">
                    <a href='.$zreLink.' target="_blank">ZRE Order('.$zreOrder.' - '.$zre->getStatusLabel().')</a>
                    </div>';
            }
        }
        $html.=$zreOrdersHtml;
        $html .= '<table class="data-grid">
        <thead>
            <th class="data-grid-th col-period no-link col-period">Product Name</th>
            <th class="data-grid-th col-period no-link col-period">Product Sku</th>
            <th class="data-grid-th col-period no-link col-period">Qty</th>';
        if ($id) {
            $html .= '<th class="data-grid-th col-period no-link col-period">Product Fresh Returned</th>';
            $html .= '<th class="data-grid-th col-period no-link col-period">Product Old Returned</th>';
            $html .= '<th class="data-grid-th col-period no-link col-period">Product Damage Returned</th>';
        }
        $html .= '</thead>
        <tbody>';
        foreach ($product as $products) {
            if ($id) {
                $Quantity = array_search($products->getId(), $productRtnQty);
                $freshQtyRtn = $oldQtyRtn = $damageQtyRtn = 0;
                if ($reqQty != '') {
                    $returnQty = array_search($products->getSku(), $reqQty);
                    if ($returnQty) {
                        $qtyReturn = explode('_', $returnQty);
                        $freshQtyRtn = $qtyReturn[1];
                        $oldQtyRtn = $qtyReturn[2];
                        $damageQtyRtn = $qtyReturn[3];
                    }
                }
                if ($Quantity) {
                    $prdRtnData = explode('_', $Quantity);
                    $html .= '<tr><td class="col-period">'.$products->getName().'</td>
                <td class="col-period">'.$products->getSku().'</td>
                <td class="col-period">
                    <input type="hidden" value="'.$prdRtnData[0].'" name="returnQuantity['.$products->getId().']"/>
                    <input style="width:70px" type="text" disabled="disabled" value="'.$prdRtnData[0].'"
                                    name="returnQty['.$products->getId().']"/>
                </td>
                <td class="col-period">
                    <input style="width:70px" type="text" disabled="disabled" value="'.$freshQtyRtn.'"
                                    name="returnQty['.$products->getId().']"/>
                </td>
                <td class="col-period">
                    <input style="width:70px" type="text" disabled="disabled" value="'.$oldQtyRtn.'"
                                    name="returnQty['.$products->getId().']"/>
                </td>
                <td class="col-period">
                    <input style="width:70px" type="text" disabled="disabled" value="'.$damageQtyRtn.'"
                                    name="returnQty['.$products->getId().']"/>
                </td>
                </tr>';
                }
            } else {
                $html .= '<tr><td class="col-period">'.$products->getName().'</td>
                <td class="col-period">'.$products->getSku().'</td>
                <td class="col-period"><input type="number" min="1" name="returnQty['.$products->getId().']"/></td></tr>';
            }
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

    /**
     * Get Req Qty
     *
     * @param int $entityId
     * @return array
     */
    public function getRequestQty($entityId)
    {
        $rmaCollection = $this->zreCollectionFactory->create();
        $rmaCollection->addFieldToFilter('order_id',$entityId);
        $rmaData = [];
        foreach($rmaCollection as $data) {
            $rmaData = ($data->getRmaData() != '')?json_decode($data->getRmaData()):[];
        }
        $qtyRtn = [];
        if(count($rmaData) >0) {
            foreach($rmaData as $dat) {
                $key = $dat->fresh.'_'.$dat->old.'_'.$dat->damage;
                $qtyRtn[$dat->sku.'_'.$key] = $dat->sku;
            }
        }
        return $qtyRtn;
    }

    /**
     * Get Order Link
     *
     * @param int $entityId
     * @return string
     */
    public function getOrderLink($orderId)
    {
        return $this->url->getUrl('sales/order/view', ['order_id' => $orderId]);
    }

    /**
     * Return Order Details
     *
     * @param string $orderId
     * @return Order
     */
    public function getReturnOrder($orderId)
    {
        return $this->order->loadByIncrementId($orderId);
    }
}
