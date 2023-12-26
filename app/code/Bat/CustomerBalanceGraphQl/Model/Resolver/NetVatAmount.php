<?php
declare(strict_types=1);

namespace Bat\CustomerBalanceGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;

class NetVatAmount implements ResolverInterface
{

    /**
     * @var CustomerRepositoryInterface
     */
     private $customerRepositoryInterface;

    /**
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
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
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $subtotal = '';
        $taxAmount = '';
        $quote = $value['model'];
        $subtotal = $quote->getSubtotal();
        $taxAmount = $quote->getGrandTotal() - $subtotal;
        return ['net' => $subtotal,
        'vat' => $taxAmount];
    }
}
