<?php

namespace Bat\BulkOrder\Block\Adminhtml;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Bat\Customer\Helper\Data as CustomerHelper;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;

class ChildOutlet extends \Magento\Backend\Block\Template
{

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

     /**
      * @var Data
      */
    protected $helper;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

     /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var StoreManagerInterface;
     */
    protected $storeManager;

    /**
     * Construct method
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param CollectionFactory $productCollectionFactory
     * @param GetSalableQuantityDataBySku $getSalableQtyDataBySku
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param CustomerHelper $customerHelper
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        CollectionFactory $productCollectionFactory,
        GetSalableQuantityDataBySku $getSalableQtyDataBySku,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        CustomerHelper $customerHelper,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->dataPersistor = $dataPersistor;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->getSalableQuantityDataBySku = $getSalableQtyDataBySku;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->customerHelper = $customerHelper;
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get Outlet Data
     */
    public function getOutletData()
    {
        return $this->dataPersistor->get('valid_outlet');
    }

    /**
     * Get Parent Id
     */
    public function getParentId()
    {
        return $this->dataPersistor->get('parent_id');
    }

    /**
     * Get Product Submit Url
     */
    public function getProductSubmitUrl()
    {
        return $this->getUrl('bulkorder/bulkorder/productsubmit');
    }

    /**
     * Get Price Tag items
     *
     * @param int $customerId
     * @return array
     */
    public function getPriceTagItems()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('pricetag_type');
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToSelect('status');
        $collection->addFieldToFilter('status', 1);
        $collection->addFieldToFilter('pricetag_type', ['eq' => 1]);
        $collection->joinField(
            'stock_status',
            'cataloginventory_stock_status',
            'stock_status',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        )->addFieldToFilter('stock_status', ['eq' => StockStatus::STATUS_IN_STOCK]);
        return $collection;
    }

    /**
     * Get Price Tag items
     *
     * @param int $customerId
     * @return array
     */
    public function getSkuItems()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('status');
        $collection->addFieldToFilter('status', 1);
        $collection->addFieldToFilter('pricetag_type', ['nin' => [1,2,3]]);
        $collection->joinField(
            'stock_status',
            'cataloginventory_stock_status',
            'stock_status',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        )->addFieldToFilter('stock_status', ['eq' => StockStatus::STATUS_IN_STOCK]);
        return $collection;
    } 

     /**
     * Get SequenceOrder products
     *
     * @return array
     */
    public function getCategorySequenceProducts() {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $rootNodeId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $category = $this->categoryFactory->create()->load($rootNodeId);

        $collectionOtherProduct = $category->getProductCollection();
        $collectionOtherProduct->addAttributeToSelect('sku');
        $collectionOtherProduct->addAttributeToSelect('name');
        $collectionOtherProduct->addAttributeToSelect('price');
        $collectionOtherProduct->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collectionOtherProduct->addAttributeToFilter('is_plp', 1);
        $collectionOtherProduct->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);
        $collectionOtherProduct->joinField(
            'stock_status',
            'cataloginventory_stock_status',
            'stock_status',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        )->addFieldToFilter('stock_status', ['eq' => StockStatus::STATUS_IN_STOCK]);
        $collectionOtherProduct->setOrder('position', 'ASC');
        return $collectionOtherProduct;
    }

    /**
     * Get Saleable Quantity
     *
     * @param string $sku
     * @return int
     */
    public function getStockStatus($sku)
    {
        $saleableQty = 1;
        $salableQtyData = $this->getSalableQuantityDataBySku->execute($sku);
        if (isset($salableQtyData[0])) {
            if ($salableQtyData[0]['manage_stock'] == 1) {
                $saleableQty = $salableQtyData[0]['qty'];
            } elseif (empty($salableQtyData[0]['manage_stock'])) {
                $saleableQty = 1;
            }
        }
        return $saleableQty;
    }

     /**
      * Get Minimum Cart Qty
      *
      * @return int
      */
    public function getMinimumCartQty()
    {
        return $this->scopeConfig->getValue("general_settings/general/minimum_qty_for_admin");
    }

    /**
     * Get Maximum Cart Qty
     *
     * @return int
     */
    public function getMaximumCartQty()
    {
        return $this->scopeConfig->getValue("general_settings/general/maximum_qty_per_cart");
    }

    /**
     * Is Allowed Price Tag
     *
     * @param string $outletId
     * @return boolean
     */
    public function isAllowedPriceTag($outletId)
    {
        $customer = $this->customerHelper->isOutletIdValidCustomer($outletId);
        $isFristOrder = $this->helper->getIsCustomerFirstOrder($customer['entity_id']);
        return $isFristOrder;
    }

    /**
     * Get Selected Qty
     *
     * @return array
     */
    public function getSelectedParams()
    {
        return $this->dataPersistor->get('quantity_params');
    }

    /**
     * Get FirstOrder Pricetag Package data
     *
     * @return array
     */
    public function getFirstOrderPriceTag()
    {
        $firstOrderPriceTagSku = [];
        $firstOrderPriceTag = $this->getFirstOrderPriceTagPackage();
        if ($firstOrderPriceTag->getSize()) {
            foreach($firstOrderPriceTag as $item){
              $firstOrderPriceTagSku[] = $item->getSku();
            }
        }
        return $firstOrderPriceTagSku;
    }

    /**
     * Get FirstOrder Pricetag Package data
     *
     * @return string
     */
    public function getFirstOrderPriceTagPackage()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('status');
        $collection->addFieldToFilter('status', 1);
        $collection->addFieldToFilter('pricetag_type', ['in' => [2,3]]);
        $collection->joinField(
            'stock_status',
            'cataloginventory_stock_status',
            'stock_status',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        )->addFieldToFilter('stock_status', ['eq' => StockStatus::STATUS_IN_STOCK]);
        return $collection;
    }
}
