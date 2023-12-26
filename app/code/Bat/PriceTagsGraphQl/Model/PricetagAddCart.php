<?php
namespace Bat\PriceTagsGraphQl\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Model\QuoteFactory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Magento\Quote\Model\QuoteMutexInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\QuoteGraphQl\Model\Cart\AddProductsToCart;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;

class PricetagAddCart
{

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @var MaskedQuoteIdToQuoteId
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var QuoteMutexInterface
     */
    private $quoteMutex;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var AddProductsToCart
     */
    private $addProductsToCart;

    /**
     * @var CollectionFactory
     */
    private $quoteItemCollectionFactory;

    /**
     * @param QuoteFactory $quoteFactory
     * @param ProductFactory $productFactory
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId
     * @param QuoteMutexInterface $quoteMutex
     * @param GetCartForUser $getCartForUser
     * @param AddProductsToCart $addProductsToCart
     * @param CollectionFactory $quoteItemCollectionFactory
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        ProductFactory $productFactory,
        CartItemRepositoryInterface $cartItemRepository,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId,
        QuoteMutexInterface $quoteMutex,
        GetCartForUser $getCartForUser,
        AddProductsToCart $addProductsToCart,
        CollectionFactory $quoteItemCollectionFactory
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->productFactory = $productFactory;
        $this->cartItemRepository = $cartItemRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->quoteMutex = $quoteMutex;
        $this->getCartForUser = $getCartForUser;
        $this->addProductsToCart = $addProductsToCart;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
    }

    /**
     * Price tag item add/update
     *
     * @param array $data
     * @param int $customerId
     * @param object $context
     * @throws GraphQlInputException
     * @return array
     */
    public function execute($data, $customerId, $context)
    {
        try {
            if (empty($this->getCartItems($customerId))) {
                throw new GraphQlInputException(__('cart item is not found. Add first add to cart product'));
            }
            /** Cart item remove start */
            $this->getRequestPriceTagItem($data['pricetag_items'], $customerId, $data['cart_id']);
            /** Cart item remove end */
            $this->quoteMutex->execute(
                [$data['cart_id']],
                \Closure::fromCallable([$this, 'run']),
                [$context, $data]
            );
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        $allItems = $this->getCartItems($customerId);
        foreach ($allItems as $allItem) {
            if (in_array($allItem->getProductId(), $this->getPriceTagTypeProductIds())) {
                $quoteItemCollection = $this->quoteItemCollectionFactory->create();
                $quoteItem = $quoteItemCollection->addFieldToFilter('item_id', $allItem['item_id']);
                foreach ($quoteItem as $item) {
                    $item->setQty(1);
                    $item->setIsPriceTag(1);
                    $item->save();
                }
            }
        }
        return $this->getCartPriceTagItems($customerId);
    }

    /**
     * Run the resolver.
     *
     * @param ContextInterface $context
     * @param array|null $args
     * @return array[]
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function run($context, ?array $args): array
    {
        $maskedCartId = $args['cart_id'];
        $cartItems = $args['pricetag_items'];
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        if (!empty($cartItems)) {
            $this->addProductsToCart->execute($cart, $cartItems);
        }
        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }

    /**
     * Get uncheck price tag item remove
     *
     * @param int $customerId
     * @param string $itemSkus
     * @return array
     */
    public function getUncheckPriceTagItem($customerId, $itemSkus)
    {
        $items = $this->getCartItems($customerId);
        $priceTagItemSkus = [];
        foreach ($items as $item) {
            if (in_array($item->getProductId(), $this->getPriceTagTypeProductIds())) {
                $priceTagItemSkus[] = $item->getSku();
            }
        }
        $ids = [];
        if (!empty($priceTagItemSkus)) {
            $uncheckSkus = array_diff($priceTagItemSkus, $itemSkus);
            $quote = $this->quoteFactory->create()->loadByCustomer($customerId);
            $quoteId = $quote->getId();
            if (!empty($uncheckSkus)) {
                foreach ($uncheckSkus as $itemSku) {
                    /** Cart item remove start */
                    $itemId = $this->getCartItemId($customerId, $itemSku);
                    try {
                        $this->cartItemRepository->deleteById($quoteId, $itemId);
                    } catch (NoSuchEntityException $e) {
                        throw new GraphQlNoSuchEntityException(__('The cart doesn\'t contain the item'));
                    } catch (LocalizedException $e) {
                        throw new GraphQlInputException(__($e->getMessage()), $e);
                    }
                    $ids[] = $this->getCartItemId($customerId, $itemSku);
                    /** Cart item remove end */
                }
            }

        }

        return $ids;
    }

    /**
     * Get Request Price Tag Items
     *
     * @param array $items
     * @param int $customerId
     * @return array
     */
    public function getRequestPriceTagItem($items, $customerId)
    {
        $skus = [];
        foreach ($items as $item) {
            $skus[] = $item['data']['sku'];
        }
        return $this->getUncheckPriceTagItem($customerId, $skus);
    }

    /**
     * Get cart item ids
     *
     * @param int $customerId
     * @param string $sku
     * @return array
     */
    public function getCartItemId($customerId, $sku)
    {
        $items = $this->getCartItems($customerId);
        $itemId = '';
        foreach ($items as $item) {
            if ($sku == $item->getSku()) {
                $itemId = $item->getItemId();
            }
        }
        return $itemId;
    }

    /**
     * Get cart price tag items
     *
     * @param int $customerId
     * @return array
     */
    public function getCartPriceTagItems($customerId)
    {
        $items = $this->getCartItems($customerId);
        $priceTagItems = [];
        $productImageDecode = [];
        $imageEncodeUrl = '';
        foreach ($items as $_item) {
            $ids[] = $_item->getProductId();
        }
        $collection = $this->productFactory->create();
        $collection->addAttributeToSelect('pricetag_type');
        $collection->addAttributeToSelect('images');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('status');
        $collection->addFieldToFilter('status', 1);
        $collection->addFieldToFilter('pricetag_type', ['eq' => 1]);
        $collection->addFieldToFilter('entity_id', [$ids]);
        $collection->joinField(
            'stock_status',
            'cataloginventory_stock_status',
            'stock_status',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        )->addFieldToFilter('stock_status', ['eq' => StockStatus::STATUS_IN_STOCK]);

        foreach ($collection as $product) {
            try {
                $attributeData = $product->getCustomAttribute('images');
                if (isset($attributeData)) {
                    $attribute = $product->getCustomAttribute('images')->getValue();
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
                'priceTagName' => $product->getName(),
                'priceTagSku' => $product->getSku()
            ];
        }
        return $priceTagItems;
    }

    /**
     * Get cart items
     *
     * @param int $customerId
     * @return array
     */
    public function getCartItems($customerId)
    {
        $quote = $this->quoteFactory->create()->loadByCustomer($customerId);
        return $items = $quote->getAllItems();
    }

    /**
     * Get Price Tag Type Product Ids
     *
     * @return array
     */
    public function getPriceTagTypeProductIds()
    {
        $collection = $this->productFactory->create();
        $collection->addAttributeToSelect('pricetag_type');
        $collection->addAttributeToSelect('images');
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
        $ids = [];
        foreach ($collection as $item) {
            $ids[] = $item->getId();
        }
        return $ids;
    }
}
