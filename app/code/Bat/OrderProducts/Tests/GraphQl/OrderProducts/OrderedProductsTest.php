<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\OrderProducts\Tests\GraphQl\OrderProducts;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class OrderedProductsTest
 * Bat\OrderProducts\Tests\GraphQl\OrderProducts
 */
class OrderedProductsTest extends GraphQlAbstract
{
  /**
   * Test Order Products
   */
    public function testOrderedProducts()
    {
        $query
        = <<<QUERY
        {
          orderProducts(order_id:3) {
              product_count
            items {
              price
              sku
              title
              quantity
              subtotal
              image
              default_attribute
            }
            
          }
        }
        
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['orderProducts']);
        $this->assertNotEmpty($response['orderProducts']);
        $this->assertNotEmpty($response['orderProducts']['product_count']);
        $this->assertNotEmpty($response['orderProducts']['items']);
        $products = $response['orderProducts']['items'];
        foreach ($products as $item) {
            $this->assertArrayHasKey('price', $item);
            $this->assertNotEmpty($item['price']);
            $this->assertArrayHasKey('sku', $item);
            $this->assertNotEmpty($item['sku']);
            $this->assertArrayHasKey('title', $item);
            $this->assertNotEmpty($item['title']);
            $this->assertArrayHasKey('quantity', $item);
            $this->assertNotEmpty($item['quantity']);
            $this->assertArrayHasKey('subtotal', $item);
            $this->assertNotEmpty($item['subtotal']);
            $this->assertArrayHasKey('image', $item);
            $this->assertNotEmpty($item['image']);
            $this->assertArrayHasKey('default_Attribute', $item);
        }
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
        $customerId = (int) $customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
