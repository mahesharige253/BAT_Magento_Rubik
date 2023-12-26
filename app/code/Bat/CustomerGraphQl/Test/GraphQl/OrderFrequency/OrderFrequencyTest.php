<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\GraphQl\OrderFrequency;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class OrderFrequencyTest
 * Bat\OrderFrequency\Test\GraphQl\OrderFrequency
 */
class OrderFrequencyTest extends GraphQlAbstract
{
  /**
   * Test Order Frequency data
   */
    public function testOrderFrequencyData()
    {
        $query
        = <<<QUERY
            {
              orderFrequency{
               cust_id
                cust_name
                order_placed
                order_frequency
                total_order
                joker_order_frequency
                message
                success
              }
            }
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['orderFrequency']);
        $this->assertNotEmpty($response['orderFrequency']);
        $orderFrequency = $response['orderFrequency'];
        $this->assertArrayHasKey('cust_id', $orderFrequency);
        $this->assertNotNull($orderFrequency['cust_id']);
        $this->assertArrayHasKey('cust_name', $orderFrequency);
        $this->assertNotNull($orderFrequency['cust_name']);
        $this->assertArrayHasKey('order_placed', $orderFrequency);
        $this->assertNotNull($orderFrequency['order_placed']);
        $this->assertArrayHasKey('order_frequency', $orderFrequency);
        $this->assertNotNull($orderFrequency['order_frequency']);
        $this->assertArrayHasKey('total_order', $orderFrequency);
        $this->assertNotNull($orderFrequency['total_order']);
        $this->assertArrayHasKey('message', $orderFrequency);
        $this->assertNotNull($orderFrequency['message']);
        $this->assertArrayHasKey('joker_order_frequency', $orderFrequency);
        $this->assertNotNull($orderFrequency['joker_order_frequency']);
        $this->assertArrayHasKey('success', $orderFrequency);
        $this->assertNotNull($orderFrequency['success']);
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
