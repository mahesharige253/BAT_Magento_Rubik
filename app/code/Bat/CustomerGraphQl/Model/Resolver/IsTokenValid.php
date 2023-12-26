<?php

declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Bat\Customer\Helper\Data;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\App\RequestInterface;
use Bat\ConcurrentSessions\Model\Device;

/**
 * Is Customer token is valid or not
 */
class IsTokenValid implements ResolverInterface
{
    public const XML_PATH_TOKEN_RENEW_MINUTES = 'bat_customer/token_renew/minutes';

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var TokenFactory
     */
    protected $tokenModelFactory;

    /**
     * @var Data
     */
    private Data $helper;

    /**
     * @var RequestInterface
     */
    private RequestInterface $requestInterface;

    /**
     * @var Device
     */
    protected $device;

    /**
     * @param GetCustomer $getCustomer
     * @param TokenFactory $tokenModelFactory
     * @param Data $helper
     * @param RequestInterface $requestInterface
     * @param Device $device
     */
    public function __construct(
        GetCustomer $getCustomer,
        TokenFactory $tokenModelFactory,
        Data $helper,
        RequestInterface $requestInterface,
        Device $device
    ) {
        $this->getCustomer = $getCustomer;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->helper = $helper;
        $this->requestInterface = $requestInterface;
        $this->device = $device;
    }
    /**
     * @inheritdoc
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
            return ['is_token_valid' => false];
        }

        //Concurrent session.
        //If customer is logged in with different device id then logout.
        $deviceId = $this->requestInterface->getHeader('Deviceid');
        if ($deviceId != null && $deviceId != '') {
            $isNewDevice = $this->device->isCurrentDevice($context->getUserId(), $deviceId);
            if (!$isNewDevice) {
                return ['is_token_valid' => false];
            }
        }

        //If customer is terminated then restrict
        $customer = $this->getCustomer->execute($context);
        $terminatedStatus = $this->helper->getCustomerTerminatedStatus($customer);
        if ($terminatedStatus) {
            return ['is_token_valid' => false, 'is_account_terminated'=> true];
        }

        $headers = $this->requestInterface->getHeader('Authorization');
        if ($headers) {
            $authorization = str_replace("Bearer ", "", $headers);
            $authToken = base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $authorization)[1])));
            $token = json_decode($authToken, true);
            $minutes = $this->helper->getSystemConfigValue(self::XML_PATH_TOKEN_RENEW_MINUTES);
            $newTime = strtotime(date('Y-m-d H:i:s', $token['iat']) .' + ' . $minutes . " minutes");
            if ((int)time() > $newTime) {
                $customerId = $customer->getId();
                $customerToken = $this->tokenModelFactory->create();
                $genrateToken = $customerToken->createCustomerToken($customerId)->getToken();
                return ['is_token_valid' => true, 'token' => $genrateToken];
            }
        }
        return ['is_token_valid' => true];
    }
}
