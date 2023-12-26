<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyData;

class OrderFrequency implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var OrderFrequencyData
     */
    private $orderFrequencyData;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param TimezoneInterface $timezoneInterface
     * @param OrderFrequencyData $orderFrequencyData
     */
    public function __construct(
        GetCustomer $getCustomer,
        TimezoneInterface $timezoneInterface,
        OrderFrequencyData $orderFrequencyData
    ) {
        $this->getCustomer = $getCustomer;
        $this->timezoneInterface = $timezoneInterface;
        $this->orderFrequencyData = $orderFrequencyData;
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
        $customer = $this->getCustomer->execute($context);
        return $this->orderFrequencyData->getOrderFrequency($customer);
    }
}
