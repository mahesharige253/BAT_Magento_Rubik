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
class PaymentOverDueTest extends GraphQlAbstract
{
   /**
    * Get best seller products
    */
    public function testProductData()
    {
        $query
            = <<<QUERY
{
paymentOverdue{
customer_id
message
status
customer_status
is_vba_change_approved
}
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['paymentOverdue']);
        $this->assertNotEmpty($response['paymentOverdue']);
        $response = $response['paymentOverdue'];
        $this->assertArrayHasKey('customer_id', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('customer_status', $response);
        $this->assertArrayHasKey('is_vba_change_approved', $response);
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = '707671@707671.com'): array
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
