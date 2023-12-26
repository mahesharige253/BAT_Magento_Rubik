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
 * Class BulkOrderValidateDataTest
 * Bat\BulkOrder\Tests\GraphQl\BulkOrder\BulkOrderValidateDataTest
 */
class BulkOrderValidateDataTest extends GraphQlAbstract
{
    /**
     * Remove Bulk Order Store
     */
    public function testRemoveBulkOrderStore()
    {
        $mutation
            = <<<MUTATION
            mutation { 
				placeBulkOrder (     
				input: { 
				    order_consent: true
				    bulkOrderItem: [
				{ 
				    outlet_id:  "702841"
				    masked_cart_id:"AXUSITwxxI6pRd1bUA8YpGY8UBPMhw9r"
				},
				{ 
				    outlet_id:  "762345"
				    masked_cart_id:"0HMzr3vha5MzWkGJJysndpz732DfPhGe"
				}]
				}
				) { 
				    bulkorder_id
				} 
			}
MUTATION;

        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['placeBulkOrder']);
        $data = $response['placeBulkOrder']['bulkorder_id'];
        $this->assertArrayHasKey('bulkorder_id', $response['placeBulkOrder']);
        $this->assertNotEmpty($response['placeBulkOrder']['bulkorder_id']);
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
