<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BestSellers\Tests\GraphQl\Products;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

/**
 * Class BestSellersTest
 */
class BestSellersProductTest extends GraphQlAbstract
{
    /**
     * Get best seller products
     */
    public function testGetBestSellerProducts()
    {
        $areaCode = '560006';
        $this->assertNotEmpty($areaCode, "Preconditions failed: Area code is not valid.");
        $this->assertTrue(is_numeric($areaCode), "Preconditions failed: Area code is not valid.");
        $query
            = <<<QUERY
{
    getBestSellers (areaCode: "{$areaCode}") {
       items {
            name
            price
            image
            product_tags{
              new
              limited
              hot
              frequent
            }
            product_url
            quantity
            stock_status
            default_attribute
        }
    }
}
QUERY;

        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['getBestSellers']);
        $this->assertArrayHasKey('items', $response['getBestSellers']);
        $this->assertIsArray($response['getBestSellers']['items']);
        $products = $response['getBestSellers']['items'];
        foreach ($products as $product) {
            $this->assertArrayHasKey('name', $product);
            $this->assertNotEmpty($product['name']);
            $this->assertArrayHasKey('price', $product);
            $this->assertNotEmpty($product['price']);
            $this->assertArrayHasKey('quantity', $product);
            $this->assertNotEmpty($product['quantity']);
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
