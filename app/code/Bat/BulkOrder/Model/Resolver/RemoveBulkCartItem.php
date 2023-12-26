<?php
namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class RemoveBulkCartItem implements ResolverInterface
{
    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * @var GetDiscountMessage
     */
    private $getDiscountMessage;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @param CartDetails $cartDetails
     * @param GetDiscountMessage $getDiscountMessage
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        CartDetails $cartDetails,
        GetDiscountMessage $getDiscountMessage,
        GetCustomer $getCustomer
    ) {
        $this->cartDetails = $cartDetails;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->getCustomer = $getCustomer;
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

        if (empty($args['input']['cart_item_id'])) {
            throw new GraphQlInputException(__('"Cart item id" value should be specified'));
        }
        $cartItemId = $args['input']['cart_item_id'];
        $maskedCartId = $args['input']['masked_cart_id'];
        $customerId = $this->cartDetails->getCustomerIdFromMaskedCartId($maskedCartId);
        $itemdiscountMessage = [];
        $discountMessage = [];
        if ($this->cartDetails->removeQuoteItem($maskedCartId, $cartItemId)) {
            $discountMessage[] = $this->getDiscountMessage->getDiscountMessage($customerId);
            $itemdiscountMessage[] = $this->getDiscountMessage->getItemMessage($customerId);
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
            $result = [
                'bulk_discount_message' => $discountMessage,
                'success' => true,
                'message' => 'Cart Item Removed Successfully'
            ];
        } else {
            $result = [
                'bulk_discount_message' => '',
                'success' => false,
                'message' => '"Masked cart id" and "cart Item Id" value should be specified'
            ];
        }
        return $result;
    }
}
