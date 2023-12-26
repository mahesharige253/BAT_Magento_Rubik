<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BulkOrder\Tests\GraphQl;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

class ValidateBulkCsvDataFailedTest extends GraphQlAbstract
{
    /**
     * Create Category
     */
    public function testValidateCsvFailedData()
    {
        $query
            = <<<MUTATION
            mutation {
                validateAndCreateQuote(
                        orderItems: [
                            {
                                outlet_id: "778689"
                                parent_outlet_id: "778689"
                                is_parent: true
                                items: [
                                    {
                                    sku: "kent-auro1"
                                    quantity: 30
                                    is_price_tag: true
                                },
                                 {
                                    sku: "Price tag Item-1"
                                    quantity: 50
                                    is_price_tag: true
                                }
                                ]
                            },
                            {
                                outlet_id: "792944"
                                parent_outlet_id: "778689"
                                is_parent: false
                                items: [
                                    {
                                    sku: "kent-new"
                                    quantity: 50
                                    is_price_tag: false
                                }
                                ]
                            }
                        ]
                ) {
                    success
                    error_message
                    bulkorder_data {
                        outlet_id
                        masked_cart_id
                    }
                }
            }
MUTATION;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['validateAndCreateQuote']);
        $this->assertNotEmpty($response['validateAndCreateQuote']);
        $this->assertEquals($response['validateAndCreateQuote']['success'], false);
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = '778689@778689.com'): array
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
