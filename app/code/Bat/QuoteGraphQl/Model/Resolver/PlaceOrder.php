<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace  Bat\QuoteGraphQl\Model\Resolver;

use Bat\Sales\Model\EdaOrderType;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Helper\Error\AggregateExceptionMessageFormatter;
use Magento\QuoteGraphQl\Model\Cart\GetCartForCheckout;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrder as PlaceOrderModel;
use Magento\QuoteGraphQl\Model\Cart\PlaceOrderMutexInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Bat\QuoteGraphQl\Helper\Data as PlaceOrderHelper;
use Bat\GetCartGraphQl\Helper\Data;
use Bat\QuoteGraphQl\Model\AfterPlaceOrder;

/**
 * @class PlaceOrder
 * Resolver for placing order after payment method has already been set
 */
class PlaceOrder extends \Magento\QuoteGraphQl\Model\Resolver\PlaceOrder
{
    /**
     * @var GetCartForCheckout
     */
    private $getCartForCheckout;

    /**
     * @var PlaceOrderModel
     */
    private $placeOrder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AggregateExceptionMessageFormatter
     */
    private $errorMessageFormatter;

    /**
     * @var PlaceOrderMutexInterface
     */
    private $placeOrderMutex;

    /**
     * @var GetCustomer
     */
    private GetCustomer $getCustomer;

    /**
     * @var PlaceOrderHelper
     */
    private PlaceOrderHelper $placeOrderHelper;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var AfterPlaceOrder
     */
    private AfterPlaceOrder $afterPlacedOrder;

    /**
     * @param GetCartForCheckout $getCartForCheckout
     * @param PlaceOrderModel $placeOrder
     * @param OrderRepositoryInterface $orderRepository
     * @param AggregateExceptionMessageFormatter $errorMessageFormatter
     * @param GetCustomer $getCustomer
     * @param PlaceOrderHelper $placeOrderHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $helper
     * @param AfterPlaceOrder $afterPlacedOrder
     * @param PlaceOrderMutexInterface|null $placeOrderMutex
     */
    public function __construct(
        GetCartForCheckout $getCartForCheckout,
        PlaceOrderModel $placeOrder,
        OrderRepositoryInterface $orderRepository,
        AggregateExceptionMessageFormatter $errorMessageFormatter,
        GetCustomer $getCustomer,
        PlaceOrderHelper $placeOrderHelper,
        CustomerRepositoryInterface $customerRepository,
        Data $helper,
        AfterPlaceOrder $afterPlacedOrder,
        ?PlaceOrderMutexInterface $placeOrderMutex = null
    ) {
        $this->getCartForCheckout = $getCartForCheckout;
        $this->placeOrder = $placeOrder;
        $this->orderRepository = $orderRepository;
        $this->errorMessageFormatter = $errorMessageFormatter;
        $this->getCustomer = $getCustomer;
        $this->placeOrderHelper =  $placeOrderHelper;
        $this->customerRepository = $customerRepository;
        $this->helper = $helper;
        $this->afterPlacedOrder = $afterPlacedOrder;
        $this->placeOrderMutex = $placeOrderMutex ?: ObjectManager::getInstance()->get(PlaceOrderMutexInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        return $this->placeOrderMutex->execute(
            $args['input']['cart_id'],
            \Closure::fromCallable([$this, 'run']),
            [$field, $context, $info, $args]
        );
    }

    /**
     * Run the resolver.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $args
     * @return array[]
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function run(Field $field, ContextInterface $context, ResolveInfo $info, ?array $args): array
    {
        $customer = '';
        $maskedCartId = $args['input']['cart_id'];
        $userId = (int)$context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $orderConsent = 0;
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__("The current customer isn\'t authorized."));
        } else {
            $customer = $this->getCustomer->execute($context);
            $customerCustomAttributes = $customer->getCustomAttributes();
            $approvalStatus = $customer->getCustomAttribute(
                'approval_status'
            )->getValue();
            if ($approvalStatus == 0) {
                throw new GraphQlInputException(
                    __('Your customer account is not approved.')
                );
            }
            $this->customerDisclosureStatus($customer);
        }
        
        try {
            $totalArLimit = 0;
            $cart = $this->getCartForCheckout->execute($maskedCartId, $userId, $storeId);
            $qty = 0;
            foreach ($cart->getAllItems() as $item) {
                if (!$item->getIsPriceTag()) {
                    $qty += $item->getQty();
                }
            }
            if ($qty < $this->helper->getMinimumCartQty()) {
                throw new GraphQlInputException(
                    __('Minimum cart cartons are required:'.$this->helper->getMinimumCartQty())
                );
            }
            if ($this->helper->getMaximumCartQty() < $qty) {
                throw new GraphQlInputException(
                    __('Maximum cart cartons are allowed:'.$this->helper->getMaximumCartQty().' or less than.')
                );
            }
            if (isset($args['input']['order_consent'])) {
                $orderConsent = $args['input']['order_consent'];
                if (!$orderConsent) {
                    throw new GraphQlNoSuchEntityException(
                        __("You must accept the terms and conditions of service to place order")
                    );
                }
            }

            // if shipping and payment method are not select then select
            if(!$cart->getShippingAddress()->getShippingMethod()){
                $shippingAddress = $cart->getShippingAddress();
                $shippingAddress->setCollectShippingRates(true)
                                ->collectShippingRates()
                                ->setShippingMethod('flatrate_flatrate');
                $shippingAddress->save();
            }

            if ($cart->getGrandTotal() > 0) {
                $bankCode = 'banktransfer';
            } else {
                $bankCode = 'free';
            }
            if ($cart->getPayment()->getMethod() != $bankCode) {
                $cart->setPaymentMethod($bankCode);
                $cart->setInventoryProcessed(false);
                $cart->getPayment()->importData(['method' => $bankCode]);
            }
           
            $cart->setEdaOrderType(EdaOrderType::ZOR);
            $cart->setOrderConsent($orderConsent);
            $cart->save();
            $customerAttributes = $customer->getCustomAttributes();
            $orderFrequency = $this->placeOrderHelper->canPlaceOrder($customer);
            if (!$orderFrequency) {
                $orderFrequencyMessage =  __("Order frequency exceeded");
                throw new GraphQlNoSuchEntityException($orderFrequencyMessage);
            }
            $isCreditCustomer = false;
            if (isset($customerAttributes['is_credit_customer'])) {
                $isCreditCustomer = $customerAttributes['is_credit_customer']->getValue();
            }
            if ($isCreditCustomer) {
                $totalArLimit = $this->getTotalArLimit($customer);
                $remainingArLimit = $this->placeOrderHelper->getRemainingArLimit($userId, $totalArLimit);
            } else {
                if ($this->placeOrderHelper->checkPaymentOverDue($userId)) {
                    $overDueMessage = $this->placeOrderHelper->getOverDueMessage();
                    throw new GraphQlNoSuchEntityException(__($overDueMessage));
                }
            }
            $orderId = $this->placeOrder->execute($cart, $maskedCartId, $userId);
            $order = $this->orderRepository->get($orderId);
            
            $this->afterPlacedOrder->afterPlace($customer, $order);
        } catch (LocalizedException $e) {
            $this->placeOrderHelper->logUnsuccessfulOrder($e->getMessage(), $userId);
            throw $this->errorMessageFormatter->getFormatted(
                $e,
                __('Unable to place order: A server error stopped your order from being placed. ' .
                    'Please try to place your order again'),
                'Unable to place order',
                $field,
                $context,
                $info
            );
        }

        return [
            'order' => [
                'order_number' => $order->getIncrementId(),
                // @deprecated The order_id field is deprecated, use order_number instead
                'order_id' => $order->getId(),
            ],
        ];
    }

    /**
     * Return Total AR Limit
     *
     * @param CustomerInterface $customer
     * @return int
     */
    public function getTotalArLimit($customer)
    {
        $totalARLimit = 0;
        if ($customer->getCustomAttribute('total_ar_limit') !='') {
            $totalARLimit = $customer->getCustomAttribute('total_ar_limit')->getValue();
        }
        return $totalARLimit;
    }

    /**
     * Customer Disclosure status
     *
     * @param object $customer
     * @return null|Exception
     */
    public function customerDisclosureStatus($customer)
    {
        $closureValue = ['6','7','8','9','10','11'];
        if ($disclosureApprovalStatus = $customer->getCustomAttribute(
            'approval_status'
        )) {
            if (in_array($disclosureApprovalStatus->getValue(),$closureValue)) {
                throw new GraphQlNoSuchEntityException(
                    __("You can't place order. Your account is under deactivation")
                );
            }
        }
    }
}
