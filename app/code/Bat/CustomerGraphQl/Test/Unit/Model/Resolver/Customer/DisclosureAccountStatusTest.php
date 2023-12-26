<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\Unit\Model\Resolver\Customer;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class BannerTest
 *
 */
class DisclosureAccountStatusTest extends GraphQlAbstract
{
   /**
    * Get best seller products
    */
    public function testProductData()
    {
        $query
            = <<<QUERY
{
    getClosureAccountDetails {
        closure_status
        status_message
        rejected_fields
        rejected_reason
    }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['getClosureAccountDetails']);
        $this->assertNotEmpty($response['getClosureAccountDetails']);
        $response = $response['getClosureAccountDetails'];
        $this->assertArrayHasKey('closure_status', $response);
        $this->assertArrayHasKey('status_message', $response);
        $this->assertArrayHasKey('rejected_fields', $response);
        $this->assertArrayHasKey('rejected_reason', $response);
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
        $customerId = (int)$customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
