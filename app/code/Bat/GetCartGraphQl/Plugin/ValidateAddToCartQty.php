<?php
namespace Bat\GetCartGraphQl\Plugin;

use Magento\QuoteGraphQl\Model\Cart\AddProductsToCart;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\GetCartGraphQl\Helper\Data;
use Bat\CustomerBalanceGraphQl\Helper\Data as CustomerBalanceHelper;
use Magento\Framework\Data\Form\FormKey;
use Magento\Catalog\Model\Product;
use Bat\BulkOrder\Block\Adminhtml\ChildOutlet;
use Magento\Checkout\Model\Cart;

class ValidateAddToCartQty
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerBalanceHelper
     */
    protected $customerBalanceHelper;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ChildOutlet
     *
     */
    protected $childOutlet;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * Validate Cart Qty
     *
     * @param Data $helper
     * @param CustomerBalanceHelper $customerBalanceHelper
     * @param FormKey $formKey
     * @param Product $product
     * @param ChildOutlet $childOutlet
     * @param Cart $cart
     */
    public function __construct(
        Data $helper,
        CustomerBalanceHelper $customerBalanceHelper,
        FormKey $formKey,
        Product $product,
        ChildOutlet $childOutlet,
        Cart $cart
    ) {
        $this->helper = $helper;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->formKey = $formKey;
        $this->product = $product;
        $this->childOutlet = $childOutlet;
        $this->cart = $cart;
    }

    /**
     * Validate Cart Qty
     *
     * @param AddProductsToCart $subject
     * @param object $result
     * @param object $cart
     * @param object $cartItems
     * @throws GraphQlInputException
     */
    public function afterExecute(AddProductsToCart $subject, $result, $cart, $cartItems)
    {
        $qty = 0;
        $isFristOrderItem = false;
        foreach ($cart->getAllItems() as $item) {
            if (!$item->getIsPriceTag()) {
                $qty += $item->getQty();
            } else{
                $isFristOrderItem = true;
            }
        }

        if (!$isFristOrderItem) {
        if ($this->customerBalanceHelper->getIsCustomerFirstOrder($cart->getCustomerId())) {
                $priceTagItems = $this->childOutlet->getFirstOrderPriceTag();
                if (!empty($priceTagItems)) {
                    $skus = [];
                    foreach($priceTagItems as $item){
                        $product = $this->product->loadByAttribute('sku', $item);
                        $params = [
                            'form_key' => $this->formKey->getFormKey(),
                            'product' => $product->getId(),
                            'qty'   => 1,
                            'price' => 0
                        ];
                        $skus[] = $item;
                        $this->cart->addProduct($product, $params);
                    }
                    $this->cart->save();

                    foreach ($this->cart->getQuote()->getAllItems() as $item) {
                        if (in_array($item->getSku(), $skus)) {
                            $item->setIsPriceTag(1);
                            $item->save();
                        }
                    }
                }
            }
        }

        if ($this->helper->getMaximumCartQty() < $qty) {
            throw new GraphQlInputException(
                __('Maximum cart cartons are allowed:'.$this->helper->getMaximumCartQty().' or less than.')
            );
        }
        return $result;
    }
}
