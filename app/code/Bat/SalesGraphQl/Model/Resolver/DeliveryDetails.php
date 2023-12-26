<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class DeliveryDetails implements ResolverInterface
{
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
        $order = $value['model'];
        $shipDate = $order->getShipDate();
        $trackingNumber = $order->getAwbNumber();
        $trackingUrl = $order->getTrackingUrl();
        $deliveredDate = $order->getActionDate();
        if($deliveredDate != ''){
            $deliveredDate = date_format(date_create($deliveredDate),'Y/m/d');
        }
        return [
            'delivery_date' => $shipDate,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'is_shipment_available' => $order->getIsShipmentAvailable(),
            'delivered_date' => $deliveredDate
        ];
    }
}
