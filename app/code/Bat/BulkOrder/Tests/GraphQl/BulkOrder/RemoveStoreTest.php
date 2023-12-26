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
 * Class RemoveStoreTest
 * Bat\BulkOrder\Tests\GraphQl\BulkOrder\RemoveStoreTest
 */
class RemoveStoreTest extends GraphQlAbstract
{
    /**
     * Remove Bulk Order Store
     */
    public function testRemoveBulkOrderStore()
    {
        $mutation
            = <<<MUTATION
            mutation{
                removeBulkOrderStore(input: {cart_id: "7KMFIWoUatJNynlZUuWyiCnSjpc82DU2"})
                {
                    message
                    success
                }
            }
MUTATION;

        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['removeBulkOrderStore']);
        $this->assertArrayHasKey('message', $response['removeBulkOrderStore']);
        $this->assertNotEmpty($response['removeBulkOrderStore']['message']);
        $this->assertArrayHasKey('success', $response['removeBulkOrderStore']);
        $this->assertNotEmpty($response['removeBulkOrderStore']['success']);
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
