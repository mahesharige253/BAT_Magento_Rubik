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
use Magento\Sales\Model\OrderFactory;
use Bat\CustomerBalanceGraphQl\Helper\Data;

class NonCreditCustomer implements ResolverInterface
{

    /**
     * @var CustomerRepositoryInterface
     */
     private $customerRepositoryInterface;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param OrderFactory                $orderFactory
     * @param Data                        $helper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepositoryInterface,
        OrderFactory $orderFactory,
        Data $helper
    ) {
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->orderFactory = $orderFactory;
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
        $quote = $value['model'];
        $store = $context->getExtensionAttributes()->getStore();
        $customerId = $context->getUserId();
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }
        
        $orderSummary = $this->helper
                ->getCustomerCartSummary(
                    $customerId,
                    $store->getWebsiteId(),
                    $quote->getGrandTotal()
                );
        if (count($quote->getAllItems()) > 0) {
            if (!$orderSummary['is_credit']) {
                if ($this->getOverDue($customerId)) {
                    throw new GraphQlNoSuchEntityException(
                        __(
                            'Overdue payment is pending'
                        )
                    );
                }
            return $orderSummary;
            }
        }
        return ['overpayment' => 0,
                'grand_total' => 0
                ];
    }

    /**
     * Get Over Due payment
     *
     * @param  int $customerId
     * @return float
     */
    public function getOverDue($customerId)
    {
        $totalDue = 0;
        $order = $this->orderFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC')->getFirstItem();
        if ($order->getStatus() != 'canceled') {
            if ($order->getStatus() == 'pending' || $order->getTotalDue() > 0) {
                $totalDue = $order->getTotalDue();
            }
        }
        return $totalDue;
    }
}
