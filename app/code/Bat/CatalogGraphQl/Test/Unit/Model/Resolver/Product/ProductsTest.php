<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Test\Unit\Model\Resolver\Product;

use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class BannerTest
 *
 */
class ProductsTest extends GraphQlAbstract
{
    /**
     * Get best seller products
     */
    public function testProductData()
    {
        $categoryId = 3;
        $pageSize = 10;
        $currentPage = 1;
        $query
            = <<<QUERY
{
  displayProducts(
    filter: {category_id: {eq: $categoryId}},
    pageSize: $pageSize,
    currentPage: $currentPage,
  ) {
    total_count
    items {
        name
        sku
      image
      product_url
      stock_status
      product_tags{
      new
      limited
      hot
      frequent
      }
      default_attribute
      price
      quantity
      category
          {
            url
            label
          }
  }
}
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['displayProducts']);
        /* This is not required as best seller might not have products. */
        $this->assertNotEmpty($response['displayProducts']);
        $products = $response['displayProducts']['items'];
        foreach ($products as $product) {
            $this->assertArrayHasKey('name', $product);
            $this->assertNotEmpty($product['sku']);
            $this->assertArrayHasKey('image', $product);
            $this->assertArrayHasKey('product_url', $product);
            $this->assertArrayHasKey('stock_status', $product);
            $this->assertArrayHasKey('product_tags', $product);
            $this->assertArrayHasKey('default_attribute', $product);
            $this->assertArrayHasKey('price', $product);
            $this->assertArrayHasKey('quantity', $product);
            $this->assertArrayHasKey('category', $product);
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
