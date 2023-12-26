<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\SalesGraphQl\Model\OrderPaymentDeadline;

class PaymentDeadline implements ResolverInterface
{
     /**
      * @var OrderPaymentDeadline
      */
    private $orderPaymentDeadline;

    /**
     * @param OrderPaymentDeadline $orderPaymentDeadline
     */
    public function __construct(
        OrderPaymentDeadline $orderPaymentDeadline
    ) {
        $this->orderPaymentDeadline = $orderPaymentDeadline;
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
        $customerId = $context->getUserId();
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }
        $order = $value['model'];
        $deadline = '';
        $deadline = $this->orderPaymentDeadline->getPaymentDeadline($order->getEntityId());
        return $deadline;
    }
}
