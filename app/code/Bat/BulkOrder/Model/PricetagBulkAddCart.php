<?php
namespace Bat\BulkOrder\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Model\QuoteMutexInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\QuoteGraphQl\Model\Cart\AddProductsToCart;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory;
use Bat\PriceTagsGraphQl\Model\PricetagAddCart;

class PricetagBulkAddCart
{

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
     * @var PricetagAddCart
     */
     protected $pricetagAddCart;

    /**
     * @param QuoteMutexInterface $quoteMutex
     * @param GetCartForUser $getCartForUser
     * @param AddProductsToCart $addProductsToCart
     * @param CollectionFactory $quoteItemCollectionFactory
     * @param PricetagAddCart $pricetagAddCart
     */
    public function __construct(
        QuoteMutexInterface $quoteMutex,
        GetCartForUser $getCartForUser,
        AddProductsToCart $addProductsToCart,
        CollectionFactory $quoteItemCollectionFactory,
        PricetagAddCart $pricetagAddCart
    ) {
        $this->quoteMutex = $quoteMutex;
        $this->getCartForUser = $getCartForUser;
        $this->addProductsToCart = $addProductsToCart;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->pricetagAddCart = $pricetagAddCart;
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
            if (empty($this->pricetagAddCart->getCartItems($customerId))) {
                throw new GraphQlInputException(__('cart item is not found. Add first add to cart product'));
            }
            /** Cart item remove start */
            $this->pricetagAddCart->getRequestPriceTagItem($data['pricetag_items'], $customerId, $data['cart_id']);
            /** Cart item remove end */
            $this->quoteMutex->execute(
                [$data['cart_id']],
                \Closure::fromCallable([$this, 'run']),
                [$context,$customerId, $data]
            );
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        $allItems = $this->pricetagAddCart->getCartItems($customerId);
        foreach ($allItems as $allItem) {
            if (in_array($allItem->getProductId(), $this->pricetagAddCart->getPriceTagTypeProductIds())) {
                $quoteItemCollection = $this->quoteItemCollectionFactory->create();
                $quoteItem  = $quoteItemCollection->addFieldToFilter('item_id', $allItem['item_id']);
                foreach ($quoteItem as $item) {
                    $item->setQty(1);
                    $item->setIsPriceTag(1);
                    $item->save();
                }
            }
        }
        return $this->pricetagAddCart->getCartPriceTagItems($customerId);
    }

    /**
     * Run the resolver.
     *
     * @param ContextInterface $context
     * @param int $customerId
     * @param array|null $args
     * @return array[]
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function run($context, $customerId, ?array $args): array
    {
        $maskedCartId = $args['cart_id'];
        $cartItems = $args['pricetag_items'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $customerId, $storeId);
        if (!empty($cartItems)) {
            $this->addProductsToCart->execute($cart, $cartItems);
        }
        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }
}
