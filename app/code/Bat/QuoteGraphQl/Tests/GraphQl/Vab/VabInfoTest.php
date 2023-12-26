<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\QuoteGraphQl\Tests\GraphQl\Vab;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class BestSellersTest
 */
class VabInfoTest extends GraphQlAbstract
{

    public function testVabInfoCheckout()
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
                      default_attribute
                    }
                    quantity
                  }
                  total_quantity
                  vba_info {
                      bank_details {
                        bank_code
                        bank_name
                      }
                      account_number
                      account_holder_name
                  }
                  
                }
            }

QUERY;
   
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customerCart']);
        $this->assertArrayHasKey('items', $response['customerCart']);
        $this->assertIsArray($response['customerCart']['items']);
        $this->assertArrayHasKey('vba_info', $response['customerCart']);
        $products = $response['customerCart']['items'];
        foreach ($products as $product) {
            $productArray = $product['product'];
            $this->assertArrayHasKey('name', $productArray);
            $this->assertNotEmpty($productArray['name']);
            $this->assertArrayHasKey('price', $productArray);
            $this->assertNotEmpty($productArray['price']);
            $this->assertArrayHasKey('image', $productArray);
            $this->assertNotEmpty($productArray['image']);
            $this->assertArrayHasKey('default_attribute', $productArray);
            $this->assertNotEmpty($productArray['default_attribute']);
        }

        $vabinfo = $response['customerCart']['vba_info'];
        
        $this->assertArrayHasKey('bank_details', $vabinfo);
        $this->assertNotEmpty($vabinfo['bank_details']);
        $bankDetails = $vabinfo['bank_details'];
        $this->assertArrayHasKey('bank_code', $bankDetails);
        $this->assertNotEmpty($bankDetails['bank_code']);
        $this->assertArrayHasKey('bank_name', $bankDetails);
        $this->assertNotEmpty($bankDetails['bank_name']);
        $this->assertArrayHasKey('account_number', $vabinfo);
        $this->assertNotEmpty($vabinfo['account_number']);
        $this->assertArrayHasKey('account_holder_name', $vabinfo);
        $this->assertNotEmpty($vabinfo['account_holder_name']);
    }

    private function getHeaderMap(string $username = 'muthu@gmail.com'): array
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
