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
 * Class Dispaly Order List
 *
 */
class OrderListTest extends GraphQlAbstract
{
    /**
     * Get Order List
     */
    public function testProductData()
    {
        $categoryId = 3;
        $pageSize = 10;
        $currentPage = 1;
        $query
            = <<<QUERY
{
  customer {
    orders(
        pageSize: 20
        currentPage: 1
        filter: { 
              status: ""
              date_from: "2023-05-07"
              date_to: "2024-07-14"
              sort: "desc"
            }
    ) {
      total_count
      items {
        id
        increment_id
        order_type
        order_date
        status
        item_name
        grand_total
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customer']);
        /* This is not required as best seller might not have products. */
        $this->assertNotEmpty($response['customer']);
        $orders = $response['customer']['orders']['items'];
        foreach ($orders as $orderData) {
            $this->assertArrayHasKey('id', $orderData);
            $this->assertArrayHasKey('increment_id', $orderData);
            $this->assertArrayHasKey('order_type', $orderData);
            $this->assertArrayHasKey('order_date', $orderData);
            $this->assertArrayHasKey('status', $orderData);
            $this->assertArrayHasKey('item_name', $orderData);
            $this->assertArrayHasKey('grand_total', $orderData);
        }
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = '527155@527155.com'): array
    {
        $objectManager = Bootstrap::getObjectManager();
        $CustomerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $customerData = $CustomerRepository->get($username);
        $customerId = (int)$customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
