<?php

declare(strict_types=1);

namespace Bat\NewProduct\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\NewProduct\Model\DataProvider\NewProductListDataProvider;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

/**
 * @class NewProductList
 * Resolver class for New products List
 */
class NewProductList implements ResolverInterface
{
    /**
     * @var NewProductListDataProvider
     */
    private NewProductListDataProvider $newProductListDataProvider;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @param NewProductListDataProvider $newProductListDataProvider
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        NewProductListDataProvider $newProductListDataProvider,
        GetCustomer $getCustomer
    ) {
        $this->newProductListDataProvider = $newProductListDataProvider;
        $this->getCustomer = $getCustomer;
    }

    /**
     * Resolver for New Product list
     *
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $areaCode = '';
        if (isset($args['areaCode'])) {
            $areaCode = $args['areaCode'];
        }

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__("The current customer isn\'t authorized."));
        } else {
            $customer = $this->getCustomer->execute($context);
            return $this->newProductListDataProvider->getNewProductList($customer, $areaCode);
        }
    }
}
