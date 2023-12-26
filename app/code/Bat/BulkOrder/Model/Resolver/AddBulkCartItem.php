<?php
namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Magento\Catalog\Model\ProductRepository;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

class AddBulkCartItem implements ResolverInterface
{
    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var GetDiscountMessage
     */
    private $getDiscountMessage;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepositoryInterface;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @param CartDetails $cartDetails
     * @param ProductRepository $productRepository
     * @param GetDiscountMessage $getDiscountMessage
     */
    public function __construct(
        CartDetails $cartDetails,
        ProductRepository $productRepository,
        GetDiscountMessage $getDiscountMessage,
        CartRepositoryInterface $cartRepositoryInterface,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
    ) {
        $this->cartDetails = $cartDetails;
        $this->productRepository = $productRepository;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        if (empty($args['input']['masked_cart_id'])) {
            throw new GraphQlInputException(__('"Masked cart id" value should be specified'));
        }
        if (
            empty($args['input']['cart_items'])
            || !is_array($args['input']['cart_items'])
        ) {
            throw new GraphQlInputException(__('Required parameter "cart_items" is missing'));
        }
        $cartId = $this->maskedQuoteIdToQuoteId->execute($args['input']['masked_cart_id']);
        $cart = $this->cartRepositoryInterface->get($cartId);
        $customerId = $this->cartDetails->getCustomerIdFromMaskedCartId($args['input']['masked_cart_id']);
        $result = [];
        foreach ($args['input']['cart_items'] as $data) {
            $sku = $data['data']['sku'];
            $maskedCartId = $args['input']['masked_cart_id'];
            $quantity = $data['data']['quantity'];
            $product = $this->productRepository->get($sku);
            $this->cartDetails->addQuoteItem($maskedCartId, $product, $quantity);
            if (sizeof($args['input']['cart_items']) == 1) {
            $this->cartRepositoryInterface->save($cart);
            }
            $discountMessage = [];
            $itemDiscountMessage = '';
            $discountMessage = $this->getDiscountMessage->getDiscountMessage($customerId);
            $itemdiscountMessage[] = $this->getDiscountMessage->getItemMessage($customerId);
            $productMatched = [];
            $productMatched = array_keys($itemdiscountMessage[0]);
            if (!empty($productMatched)) {
                foreach ($productMatched as $productCondition) {
                    if ($sku == $productCondition) {
                        $itemDiscountMessage = $itemdiscountMessage[0][$sku];
                    }
                }
                if (!empty($itemdiscountMessage[0])) {
                    foreach ($itemdiscountMessage[0] as $itemDiscount) {
                        if ($itemDiscount == '{"context":"benefit"}') {
                            $i = array_search('{"context":"benefit"}', $itemdiscountMessage[0]);
                            unset($itemdiscountMessage[0][$i]);
                        }
                    }
                    if (!empty($itemdiscountMessage[0])) {
                        $discountMessage = $itemdiscountMessage[0];
                    } else {
                        $discountMessage = ['{"context":"benefit"}'];
                    }
                }
            }
            if (sizeof($args['input']['cart_items']) == 1) {
                $result = [
                    'bulk_discount_message' => $discountMessage,
                    'bulk_item_message' => $itemDiscountMessage,
                    'success' => true,
                    'message' => 'Cart Item Added Successfully'
                ];
            }
        }
        if (sizeof($args['input']['cart_items']) > 1) {
            $itemDiscountMessage = '';
            $discountMessage = '';
            $this->cartRepositoryInterface->save($cart);
            $discountMessage = $this->getDiscountMessage->getDiscountMessage($customerId);
            $result = [
                'bulk_discount_message' => $discountMessage,
                'bulk_item_message' => $itemDiscountMessage,
                'success' => true,
                'message' => 'Cart Item Added Successfully'
            ];
        }
        return $result;
    }
}