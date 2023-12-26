<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BulkOrder\Tests\GraphQl\BulkOrder;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

/**
 * Class AddUpdateBulkPriceTagTest
 * Bat\BulkOrder\Tests\GraphQl\BulkOrder\RemoveStoreTest
 */
class AddUpdateBulkPriceTagTest extends GraphQlAbstract
{
    /**
     * Remove Bulk Order Store
     */
    public function testRemoveBulkOrderStore()
    {
        $mutation
            = <<<MUTATION
            mutation {
              addBulkOrderPriceTagItems(
                input: {
                  cart_id: "UeFW1ezIziuLnHAeQePfqEHoLIUvkmHt"
                  outlet_id: "392762"
                  pricetag_items: [
                     {
                      data:{sku: "Price tag Item-1"  
                        quantity: 1 } 
                    },
                    {
                      data:{sku: "Price tag Item-2"  
                        quantity: 1 } 
                    }
                  ]
                }
              ) {
                   
                            priceTagImage
                            priceTagName
                            priceTagSku
                  
              }
            }
        MUTATION;

        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());
        foreach ($response['addBulkOrderPriceTagItems'] as $data) {
            $this->assertArrayHasKey('priceTagImage', $data);
            $this->assertNotEmpty($data['priceTagName']);
            $this->assertArrayHasKey('priceTagName', $data);
            $this->assertNotEmpty($data['priceTagSku']);
            $this->assertArrayHasKey('priceTagSku', $data);
        }
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = '310510@310510.com'): array
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
