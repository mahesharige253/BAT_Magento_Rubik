<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\Unit\Model\Resolver\Customer;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class Cart Items Display
 *
 */
class CartItemsTest extends GraphQlAbstract
{

    /**
     * Get Cart Items
     */
    public function testCartData()
    {
        $query
            = <<<QUERY
{
    customerCart {
    id
    items { 
    id
     uid
      product {
        name
        sku
        image
        price
        stock_status
        default_attribute
      }
      quantity
    }
    total_quantity
     prices{
      subtotal_excluding_tax{
        value
      }
      subtotal_including_tax{
        value
      }
      grand_total{
        value
      }

    }
    applied_store_credit{
        applied_balance {
            value
        }
            current_balance{
            value
        }
              enabled
    }
       }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customerCart']);
        $this->assertNotEmpty($response['customerCart']);
        $response = $response['customerCart'];
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey('total_quantity', $response);
        $this->assertArrayHasKey('prices', $response);
        $this->assertArrayHasKey('applied_store_credit', $response);
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = 'ansh@mailinator.com'): array
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
