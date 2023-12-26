<?php
namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\BulkOrder\Model\ValidateCreateBulkOrder;

class CreateBulkOrder implements ResolverInterface
{
    /**
     * @var ValidateCreateBulkOrder
     */
    private $validateCreateBulkOrder;

    /**
     * @param ValidateCreateBulkOrder $validateCreateBulkOrder
     */
    public function __construct(
        ValidateCreateBulkOrder $validateCreateBulkOrder
    ) {
        $this->validateCreateBulkOrder = $validateCreateBulkOrder;
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
      
        if (empty($args['input']['bulkOrderItem']) || !is_array($args['input']['bulkOrderItem'])) {
            throw new GraphQlInputException(__('"bulkOrderItems" value should be specified'));
        }
        if (empty($args['input']['order_consent'])) {
            throw new GraphQlInputException(__('"order_consent" value should be specified'));
        }

        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $customerId = $context->getUserId();

        return $this->validateCreateBulkOrder->placeOrder(
            $args['input']['bulkOrderItem'],
            $args['input']['order_consent'],
            $customerId,
            $storeId
        );
    }
}
