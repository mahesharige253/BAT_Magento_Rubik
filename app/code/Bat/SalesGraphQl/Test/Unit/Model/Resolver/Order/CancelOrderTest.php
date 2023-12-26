<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\SalesGraphQl\Test\Unit\Model\Resolver\Order;

use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class Cancel Order By Increment Id
 *
 */
class CancelOrderTest extends GraphQlAbstract
{
    /**
     * Cancel Order
     */
    public function testCancelOrder()
    {
        $mutation
        = <<<MUTATION
mutation {
cancelOrder(input: {order_id:"99999999999999"}) {
message
success
}
}
MUTATION;

        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());

        $this->assertIsArray($response['cancelOrder']);
        $this->assertNotEmpty($response['cancelOrder']);
        $orders = $response['cancelOrder'];
        $this->assertArrayHasKey('message', $orders);
        $this->assertArrayHasKey('success', $orders);
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = '707671@707671.com'): array
    {
        $objectManager = Bootstrap::getObjectManager();
        $CustomerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $customerData = $CustomerRepository->get($username);
        $customerId = (int) $customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
