<?php
declare(strict_types=1);

namespace Bat\CustomerBalanceGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\OrderProducts\Helper\Data;

class PaymentDeadline implements ResolverInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * Construct method
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized. Try again with authorization token'
                )
            );
        }

        $result = '';
        $deadline = $this->helper->getPaymentDeadline();
        $result = date("Y-m-d h:i:s", strtotime($deadline));
        return $result;
    }
}
