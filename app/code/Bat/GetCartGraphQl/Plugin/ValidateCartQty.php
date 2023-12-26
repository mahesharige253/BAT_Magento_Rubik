<?php
namespace Bat\GetCartGraphQl\Plugin;

use Magento\QuoteGraphQl\Model\Cart\UpdateCartItem;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\GetCartGraphQl\Helper\Data;

class ValidateCartQty
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Validate Cart Qty
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Validate Cart Qty
     *
     * @param UpdateCartItem $subject
     * @param object $result
     * @param object $cart
     * @param int $cartItemId
     * @param int $quantity
     * @throws GraphQlInputException
     */
    public function afterExecute(UpdateCartItem $subject, $result, $cart, $cartItemId, $quantity)
    {
        $qty = 0;
        foreach ($cart->getAllItems() as $item) {
            if (!$item->getIsPriceTag()) {
                $qty += $item->getQty();
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
