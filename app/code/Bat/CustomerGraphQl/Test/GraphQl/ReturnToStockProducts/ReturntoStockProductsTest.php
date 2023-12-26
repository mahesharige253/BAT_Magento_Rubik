<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\GraphQl\ReturnToStockProducts;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class ReturntoStockProductsTest
 * Bat\CustomerGraphQl\Test\GraphQl\ReturntoStockProductsTest
 */
class ReturntoStockProductsTest extends GraphQlAbstract
{
    /**
     * Test Return to Stock Product data
     */
    public function testReturntoStockProducts()
    {
        $query
            = <<<QUERY
        {
            productCollection {
                items{
                name
                sku
                image
                flavor
                color
            }
            }
          }
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['productCollection']);
        $this->assertNotEmpty($response['productCollection']);
        $this->assertIsArray($response['productCollection']['items']);
        $this->assertNotEmpty($response['productCollection']['items']);
        $productData = $response['productCollection']['items'];
        foreach ($productData as $product) {
            $this->assertArrayHasKey('name', $product);
            $this->assertNotNull($product['name']);
            $this->assertArrayHasKey('sku', $product);
            $this->assertNotNull($product['sku']);
            $this->assertArrayHasKey('image', $product);
            $this->assertNotNull($product['image']);
            $this->assertArrayHasKey('flavor', $product);
            $this->assertArrayHasKey('color', $product);
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
