<?php
namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\BulkOrder\Model\Resolver\CartDetails;

class RemoveBulkOrderCart implements ResolverInterface
{
    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * @param  CartDetails $cartDetails
     */
    public function __construct(
        CartDetails $cartDetails
    ) {
        $this->cartDetails = $cartDetails;
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
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('"cart id" value should be specified'));
        }

        $maskedCartId = $args['input']['cart_id'];
        $val = $this->cartDetails->setCartInactive($maskedCartId);
        if ($val == '') {
            $result = [
                'success' => true,
                'message' => 'Store Removed Successfully'
            ];
        }
        return $result;
    }
}
