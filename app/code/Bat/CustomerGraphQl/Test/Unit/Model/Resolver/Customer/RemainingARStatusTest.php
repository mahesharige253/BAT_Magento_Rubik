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
 * Class Remaining AR Status
 *
 */
class RemainingARStatusTest extends GraphQlAbstract
{
    /**
     * Get AR Remaining
     */
    public function testReimainingArData()
    {
        $query
            = <<<QUERY
{
remainingARLimit{
customer_id
total_ar_limit
remaining_ar
}
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['remainingARLimit']);
        $this->assertNotEmpty($response['remainingARLimit']);
        $response = $response['remainingARLimit'];
        $this->assertArrayHasKey('customer_id', $response);
        $this->assertArrayHasKey('total_ar_limit', $response);
        $this->assertArrayHasKey('remaining_ar', $response);
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
