<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ReturnDetails implements ResolverInterface
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
        $returnedDate = $order->getActionDate();
        if($returnedDate != ''){
            $returnedDate = date_format(date_create($returnedDate),'Y/m/d');
        }
        return [
            'return_date' => $shipDate,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
            'is_shipment_available' => $order->getIsShipmentAvailable(),
            'returned_date' => $returnedDate
        ];
    }
}
