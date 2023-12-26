<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BulkOrder\Model\Resolver;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Eav\Model\Config;
use Magento\Quote\Model\Quote\Item;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Pricing\Helper\Data as PricingData;

/**
 * @inheritdoc
 */
class CartDetails
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepositoryInterface;

    /**
     * @var CollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @var Config
     */
    private $_eavConfig;

    /**
     * @var Item
     */
    protected $quoteItem;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var PricingData
     */
    protected $pricingHelper;

    /**
     *
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param CollectionFactory $customerCollectionFactory
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param Config $eavConfig
     * @param Item $quoteItem
     * @param Data $helper
     * @param PricingData $pricingData
     */
    public function __construct(
        CartRepositoryInterface $cartRepositoryInterface,
        CollectionFactory $customerCollectionFactory,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        Config $eavConfig,
        Item $quoteItem,
        Data $helper,
        PricingData $pricingHelper
    ) {
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->_eavConfig = $eavConfig;
        $this->quoteItem = $quoteItem;
        $this->helper = $helper;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * Get Customer id from Customer outlet ID.
     *
     * @param string $outletIds
     * @return array
     */
    public function getCustomerIdsByCustomAttribute($outletIds)
    {
        $attributeCode = 'outlet_id';

        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToSelect('entity_id');
        $customerCollection->addAttributeToFilter($attributeCode, $outletIds);

        return $customerCollection->getAllIds();
    }

    /**
     * Get Customer id from Masked cart id.
     *
     * @param string $maskedCartId
     * @return int
     */
    public function getCustomerIdFromMaskedCartId($maskedCartId)
    {
        $customerId = '';
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        $customerId = $cart->getCustomerId();
        return $customerId;
    }

    /**
     * Get Product Sku from cart item id.
     *
     * @param string $cartItemId
     * @param string $maskedCartId
     * @return string
     */
    public function getProductSkuFromCartItemId($cartItemId, $maskedCartId)
    {
        $itemSku = '';
            $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
            $cart = $this->cartRepositoryInterface->get($cartId);
            $cartItem = $cart->getItems();
        foreach ($cartItem as $item) {
            if ($item->getId() == $cartItemId) {
                $itemSku = $item->getSku();
            }
        }
        return $itemSku;
    }

    /**
     * Get CartItems total Count.
     *
     * @param string $maskedCartId
     * @return int
     */
    public function getCartItemCount($maskedCartId)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        return $cart->getItemsCount();
    }

    /**
     * Get whether cart is active or not.
     *
     * @param string $maskedCartId
     * @return boolean
     */
    public function getCartActive($maskedCartId)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        return $cart->getIsActive();
    }

    /**
     * Set a Cart as Inactive.
     *
     * @param string $maskedCartId
     * @return boolean
     */
    public function setCartInactive($maskedCartId)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        $cart->setIsActive(false);
        $cart->save();
        return $cart->getIsActive();
    }

    /**
     * Returns masked cartId based on EntityId.
     *
     * @param string $entityId
     * @return string
     */
    public function getQuoteMaskedIdByEntityId($entityId)
    {
        return $this->quoteIdToMaskedQuoteId->execute($entityId);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabelByValue($attributeCode, $entityType, $value)
    {
        try {
            $entityType = $this->_eavConfig->getEntityType($entityType);
            $attribute = $this->_eavConfig->getAttribute($entityType, $attributeCode);
            $options = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Get Minimum Payment
     *
     * @param float $remainingAr
     * @param float $subtotal
     * @param float $totalArLimit
     * @param boolean $isCreditCustomer
     * @return float
     */
    public function getMinimumPayment($remainingAr, $subtotal, $totalArLimit, $isCreditCustomer)
    {
        $minimumPayment = 0;
        $overpaymentvalue = 0;
        if (!empty($isCreditCustomer)) {
            if ($remainingAr) {
                if (($remainingAr < $totalArLimit) && $remainingAr > $subtotal) {
                    return $minimumPayment;
                }
                if (($remainingAr < $totalArLimit) && $remainingAr < $subtotal) {
                    $minimumPayment = $subtotal - $remainingAr;
                    return $minimumPayment;
                }
                if (($remainingAr == $totalArLimit) && $remainingAr >= $subtotal) {
                    return $minimumPayment;
                } elseif (($remainingAr == $totalArLimit) && $remainingAr < $subtotal) {

                    if ($overpaymentvalue < $remainingAr) {
                        $totalRemaining = $remainingAr + $overpaymentvalue;
                        $minimumPayment = $subtotal - $totalRemaining;
                    }
                    return $minimumPayment;
                }
            }
            if (($remainingAr == 0) && $overpaymentvalue == 0) {
                return $subtotal;
            }
        }

        return $minimumPayment;
    }

    /**
     * Remove Quote Item from particular store
     *
     * @param string $maskedCartId
     * @param int $itemId
     * @return boolean
     */
    public function removeQuoteItem($maskedCartId, $itemId)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        try {
            $isExistItem = $cart->getItemById($itemId);
            if (!empty($isExistItem)) {
                $quoteItem = $this->quoteItem->load($itemId);
                $quoteItem->delete();
                $cart->setTriggerRecollect(1);
                $cart->collectTotals()->save();
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Get CartItems qty.
     *
     * @param object $cart
     * @return int
     */
    public function getCartItemsQty($cart)
    {
        $qty = 0;
        foreach ($cart->getAllItems() as $item) {
            if ($item->getIsPriceTag() != true) {
                $qty += $item->getQty();
            }

        }
        return $qty;
    }

    /**
     * Get CartItems count.
     *
     * @param object $cart
     * @return int
     */
    public function getCartItemsCount($cart)
    {
        $count = 0;
        foreach ($cart->getAllItems() as $item) {
            if ($item->getIsPriceTag() != true) {
                $count++;
            }

        }
        return $count;
    }

     /**
      * Get Cart Items
      *
      * @param int $customerId
      * @return array
      */
    public function getCartItems($customerId)
    {
        $arr = [];
        $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
        if ($quote->getId()) {
            foreach ($quote->getAllItems() as $item) {
                $sku = $item->getSku();
                $arr[] = $sku;
            }
        }
        return $arr;
    }

    /**
     * Get Cart Items ID
     *
     * @param int $customerId
     * @return array
     */
    public function getCartItemsId($customerId)
    {
        $cartItemsId = [];
        $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
        if ($quote->getId()) {
            foreach ($quote->getAllItems() as $item) {
                $id = $item->getId();
                $cartItemsId[] = $id;
            }
        }
        return $cartItemsId;
    }

    /**
     * Update cart item
     *
     * @param string $maskedCartId
     * @param int $itemId
     * @param int $quantity
     * @return boolean
     */
    public function updateQuoteItem($maskedCartId, $itemId, $quantity)
    {
        $newQty = '';
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        $allItems = $cart->getAllItems();
        $this->validateUpdatetoCart($maskedCartId, $itemId, $quantity);
        foreach ($allItems as $item) {
            if ($item->getItemId() == $itemId) {
                $newQty = $item->setQty($quantity);
            }
        }
        $this->cartRepositoryInterface->save($cart);
        return $newQty;
    }

    /**
     * Add Item to Cart
     *
     * @param string $maskedCartId
     * @param string $product
     * @param int $quantity
     * @return boolean
     */
    public function addQuoteItem($maskedCartId, $product, $quantity)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        $this->validateAddtoCart($maskedCartId, $quantity);
        $cart->addProduct($product, $quantity);
        return true;
    }

    /**
     * Validate add to cart
     *
     * @param string $maskedCartId
     * @param int $quantity
     * @return boolean
     */
    public function validateAddtoCart($maskedCartId, $quantity)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        $allItems = $cart->getAllItems();
        $existingQty = 0;
        $totalReceivedQty = 0;
        foreach ($allItems as $item) {
            if (!$item->getIsPriceTag()) {
                $existingQty += (int) $item->getQty();
            }
        }
        $totalReceivedQty = $quantity;
        $totalAvailQty = $totalReceivedQty + $existingQty;
        if ($existingQty > 0 && $totalAvailQty < $this->helper->getMinimumCartQty()) {
            throw new GraphQlInputException(
                __('Minimum Product quantity are required:' . $this->helper->getMinimumCartQty())
            );
        }
        if ($this->helper->getMaximumCartQty() < $totalAvailQty) {
            throw new GraphQlInputException(
                __('Maximum Product quantity are allowed:' . $this->helper->getMaximumCartQty() . ' or less than.')
            );
        }

        return true;
    }

    /**
     * Validate update to cart
     *
     * @param string $maskedCartId
     * @param int $itemId
     * @param int $quantity
     * @return boolean
     */
    public function validateUpdatetoCart($maskedCartId, $itemId, $quantity)
    {
        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepositoryInterface->get($cartId);
        $allItems = $cart->getAllItems();
        $existingQty = 0;
        $totalReceivedQty = 0;
        foreach ($allItems as $item) {
            if (!$item->getIsPriceTag()) {
                if ($item->getItemId() == $itemId) {
                    continue;
                }
                $existingQty += (int) $item->getQty();
            }
        }
        $totalReceivedQty = $quantity;
        $totalAvailQty = $totalReceivedQty + $existingQty;

        // if ($totalAvailQty < $this->helper->getMinimumCartQty()) {
        //     throw new GraphQlInputException(
        //         __('Minimum Product quantity are required:' . $this->helper->getMinimumCartQty())
        //     );
        // }
        if ($totalAvailQty > $this->helper->getMaximumCartQty()) {
            throw new GraphQlInputException(
                __('Maximum Product quantity are allowed:' . $this->helper->getMaximumCartQty() . ' or less than.')
            );
        }

        return true;
    }

    /**
     * Get cart discount data
     *
     * @param object $cart
     * @return string|int
     */
    public function getDiscountData($cart)
    {
        if ($cart->getAppliedRuleIds()) {
            $cart->collectTotals();
            $cartDiscount = $cart->getSubtotal() - $cart->getSubtotalWithDiscount();
            $discountAmount = $this->pricingHelper->currency(-$cartDiscount, false, false);
        } else {
            $discountAmount = 0;
        }

        return $discountAmount;
    }
}
