<?php
namespace Bat\PriceTagsGraphQl\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\Catalog\Model\ProductFactory;
use Bat\BulkOrder\Block\Adminhtml\ChildOutlet;

class PriceTagList
{

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ChildOutlet
     */
    protected $childOutlet;

    /**
     * @param QuoteFactory $quoteFactory
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Data $helper
     * @param ProductFactory $productFactory
     * @param ChildOutlet $childOutlet
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Data $helper,
        ProductFactory $productFactory,
        ChildOutlet $childOutlet
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->productFactory = $productFactory;
        $this->childOutlet = $childOutlet;
    }

    /**
     * Get price tag items
     *
     * @param int $customerId
     * @throws GraphQlInputException
     */
    public function execute($customerId, $args = null)
    {
        try {
            if (!empty($customerId)) {
                $priceTagProduct = $this->getPriceTagItems();
                $priceTagItems = [];
                $productImageDecode = [];
                $customAttributeValue = '';
                //if ($this->helper->getIsCustomerFirstOrder($customerId) == false
                 //   || (isset($args['is_bulkorder']) && $args['is_bulkorder'] == 1)) {
                if (isset($args['is_bulkorder']) && $args['is_bulkorder'] == 2) {
                    $skus = $this->childOutlet->getFirstOrderPriceTag();
                    foreach ($skus as $sku) {
                        $product = $this->productFactory->create()->loadByAttribute('sku', $sku);
                        if (!empty($product)) {
                            $imageEncodeUrl = '';
                            $customAttributeValue = $product->getCustomAttribute('images');
                            if ($customAttributeValue) {
                                $attribute = $customAttributeValue->getValue();
                                if ($attribute != '') {
                                    $productImageDecode = json_decode($attribute);
                                }
                                if (!empty($productImageDecode) && is_array($productImageDecode)) {
                                    $data = get_object_vars($productImageDecode[0]);
                                    $imageEncodeUrl = base64_encode($data['fileURL']);
                                }
                            }
                            $priceTagItems[] = [
                                'priceTagImage' => $imageEncodeUrl,
                                'priceTagName' => $product->getName(),
                                'priceTagSku' => $product->getSku(),
                                'pricetag_type' => $product->getPricetagType()
                            ];
                        }
                    }
                } else {
                    foreach ($priceTagProduct as $item) {
                        $imageEncodeUrl = '';
                        $sku = $item->getSku();
                        $product = $this->productFactory->create()->loadByAttribute('sku', $sku);
                        try {
                            $customAttributeValue = $product->getCustomAttribute('images');
                            if ($customAttributeValue) {
                                $attribute = $customAttributeValue->getValue();
                                if ($attribute != '') {
                                    $productImageDecode = json_decode($attribute);
                                }
                                if (!empty($productImageDecode) && is_array($productImageDecode)) {
                                    $data = get_object_vars($productImageDecode[0]);
                                    $imageEncodeUrl = base64_encode($data['fileURL']);
                                }
                            }
                        } catch (Exception $e) {
                            $imageEncodeUrl = '';
                        }

                        $priceTagItems[] = [
                            'priceTagImage' => $imageEncodeUrl,
                            'priceTagName' => $item->getName(),
                            'priceTagSku' => $item->getSku(),
                            'pricetag_type' => $product->getPricetagType()
                        ];
                    }
                }
            } else {
                throw new GraphQlInputException(__('Not found customerId'));
            }
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        return $priceTagItems;
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
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('sku');
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
     * Get Media Url
     *
     * @return string
     */
    public function getMediaUrl()
    {
        $prodPath = 'catalog/product';
        return $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $prodPath;
    }
}
