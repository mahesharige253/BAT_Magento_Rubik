<?php
namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class OrderShippingNotification implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        GetCustomer $getCustomer,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->getCustomer = $getCustomer;
        $this->orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
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
        $customerId = $customer->getId();
        $dueAmount = '';

        $order = $this->orderFactory->create()
                ->getCollection()
                ->addFieldToFilter('status', 'processing')
                ->addFieldToFilter('customer_id', $customerId);
        if (count($order)) {

            $orderIds = '';
            $orderCount = count($order);
            foreach ($order as $orderData) {
                    $orderIds .= '#'.$orderData->getIncrementId().' ';
            }
            $status = true;
            /* Order Shipping Notification */
            if ($orderCount == 1) {
                $message = __('Your Order ').$orderIds.__('is being delivered.');
            } else {
                $message = __('Your Order ').$orderIds.__('are being delivered.');
            }
        } else {
            $status = false;
            $message = __('');
        }

        $result = [
            'customer_id' => $customerId,
            'status' => $status,
            'message' => $message
        ];
        return $result;
    }
}
