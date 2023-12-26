<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\SalesGraphQl\Model\CancelOrderModel;

class CancelOrder implements ResolverInterface
{
    /**
     * @var CancelOrderModel
     */
    protected $cancelOrder;

    /**
     * @param CancelOrderModel $cancelOrder
     */
    public function __construct(
        CancelOrderModel $cancelOrder
    ) {
        $this->cancelOrder = $cancelOrder;
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
        if (isset($args['input']['order_id']) && $args['input']['order_id'] !='') {
            $incrementId = $args['input']['order_id'];
            $customerId = $context->getUserId();
            return $this->cancelOrder->orderCancelByIncrementId($incrementId, $customerId);
        } else {
            $response['success'] = false;
            $response['message'] = __('Please enter order id');
            return $response;
        }
    }
}
