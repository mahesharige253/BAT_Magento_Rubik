<?php
namespace Bat\PriceTagsGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\PriceTagsGraphQl\Model\PriceTagList;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class PriceTag implements ResolverInterface
{
    /**
     * @var PriceTagList
     */
    private $priceTagList;

     /**
      * @var GetCustomer
      */
    private $getCustomer;

    /**
     * @param PriceTagList $priceTagList
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        PriceTagList $priceTagList,
        GetCustomer $getCustomer
    ) {
        $this->priceTagList = $priceTagList;
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
        $customer = $this->getCustomer->execute($context);
        return $this->priceTagList->execute($customer->getId(), $args);
    }
}
