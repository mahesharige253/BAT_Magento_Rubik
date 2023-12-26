<?php

namespace Bat\ConcurrentSessions\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\ConcurrentSessions\Model\Device;

class RegisterNewDevice implements ResolverInterface
{
    /**
     * @var Device
     */
    private $device;

    /**
     * @inheritdoc
     *
     * @param Device $device
     */
    public function __construct(
        Device $device
    ) {
        $this->device = $device;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if (!isset($args['input']['device_id']) || trim($args['input']['device_id']) == '') {
            throw new GraphQlInputException(__('Device ID is mandatory.'));
        }

        return $this->device->addDeviceToHistory($context->getUserId(), $args['input']['device_id']);
    }
}
