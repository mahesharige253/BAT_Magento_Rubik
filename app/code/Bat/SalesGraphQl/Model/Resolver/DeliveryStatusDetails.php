<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\OrderRepository;

class DeliveryStatusDetails implements ResolverInterface
{
    /**
      * @var OrderRepository
      */
    private $orderRepository;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        OrderRepository $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
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

        if(isset($value['id'])) {

            $order = $this->orderRepository->get($value['id']);
            $deliveredDate = $order->getActionDate();
            if($deliveredDate != ''){
                $deliveredDate = date_format(date_create($deliveredDate),'Y/m/d');
            }
            return [
                'delivery_date' => $order->getShipDate(),
                'tracking_number' => $order->getAwbNumber(),
                'tracking_url' => $order->getTrackingUrl(),
                'is_shipment_available' => $order->getIsShipmentAvailable(),
                'delivered_date' => $deliveredDate
            ];
        }
        return [
                'delivery_date' => null,
                'tracking_number' => null,
                'tracking_url' => null,
                'is_shipment_available' => null,
                'delivered_date' => null
            ];

    }
}
