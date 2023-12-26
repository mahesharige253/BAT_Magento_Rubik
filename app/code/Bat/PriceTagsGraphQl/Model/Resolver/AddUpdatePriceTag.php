<?php
namespace Bat\PriceTagsGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\PriceTagsGraphQl\Model\PricetagAddCart;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class AddUpdatePriceTag implements ResolverInterface
{
    /**
     * @var PricetagAddCart
     */
    private $pricetagAddCart;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @param PricetagAddCart $pricetagAddCart
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        PricetagAddCart $pricetagAddCart,
        GetCustomer $getCustomer
    ) {
        $this->pricetagAddCart = $pricetagAddCart;
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
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
        $customer = $this->getCustomer->execute($context);
        return $this->pricetagAddCart->execute($args['input'], $customer->getId(), $context);
    }
}
