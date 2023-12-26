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
use Bat\CustomerBalanceGraphQl\Helper\Data;

class CreditCustomer implements ResolverInterface
{

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param Data                        $helper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        Data $helper
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
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
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $store = $context->getExtensionAttributes()->getStore();
        $customerId = $context->getUserId();
        $quote = $value['model'];
        $quoteId = $quote->getId();
        $quote->collectTotals();
        $orderSummary = $this->helper
                                ->getCustomerCartSummary($customerId, $store->getWebsiteId(), $quote->getGrandTotal());
        if ($orderSummary['is_credit']) {
            return $orderSummary;
        }
        return ['remaining_ar' => 0,
               'overpayment' => 0,
               'minimum_payment' => 0,
               'grand_total' => 0
               ];
    }
}
