<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\NewProduct\Tests\GraphQl\Products;

use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Integration\Model\Oauth\TokenFactory;

/**
 * @Class NewProductsTest
 * Test Case for New/Recommended products list
 */
class NewProductsTest extends GraphQlAbstract
{
    /**
     * @throws \Exception
     * Get New/Recommended Products
     */
    public function testGetNewProductsList()
    {
        $query
            = <<<QUERY
            {
              getNewProducts {
                items {
                  default_attribute
                  image
                  name
                  price
                  product_tags{
                  new
                  limited
                  hot
                  frequent
                  }
                  product_url
                  quantity
                  sku
                  stock_status
                }
                title
              }
            }
        QUERY;

        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['getNewProducts']);
        $this->assertArrayHasKey('items', $response['getNewProducts']);
        $this->assertIsArray($response['getNewProducts']['items']);
        $this->assertArrayHasKey('title', $response['getNewProducts']);

        $products = $response['getNewProducts']['items'];
        foreach ($products as $product) {
            $this->assertArrayHasKey('name', $product);
            $this->assertNotEmpty($product['name']);
            $this->assertArrayHasKey('sku', $product);
            $this->assertNotEmpty($product['sku']);
            $this->assertArrayHasKey('image', $product);
            $this->assertNotEmpty($product['image']);
            $this->assertArrayHasKey('price', $product);
            $this->assertNotEmpty($product['price']);
            $this->assertArrayHasKey('quantity', $product);
            $this->assertIsInt($product['quantity']);
            $this->assertArrayHasKey('stock_status', $product);
            $this->assertNotEmpty($product['stock_status']);
            $this->assertArrayHasKey('product_url', $product);
            $this->assertNotEmpty($product['product_url']);
            $this->assertArrayHasKey('product_tags', $product);
        }
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = 'test@gmail.com'): array
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
