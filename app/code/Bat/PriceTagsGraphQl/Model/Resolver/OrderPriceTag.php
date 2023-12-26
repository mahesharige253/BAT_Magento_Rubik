<?php
namespace Bat\PriceTagsGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\PriceTagsGraphQl\Model\OrderPriceTagList;

class OrderPriceTag implements ResolverInterface
{
    /**
     * @var OrderPriceTagList
     */
    private $orderPriceTagList;

    /**
     * @param OrderPriceTagList $orderPriceTagList
     */
    public function __construct(
        OrderPriceTagList $orderPriceTagList
    ) {
        $this->orderPriceTagList = $orderPriceTagList;
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
       
        return $this->orderPriceTagList->execute($args);
    }
}
