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
 * @class ChangePinAndPasswordTest
 * Test case change pin and password
 */
class ChangePinAndPasswordTest extends GraphQlAbstract
{
    /**
     * Update the customer's pin and password
     */
    public function testChangePinAndPassword()
    {
        $query
            = <<<MUTATION
                mutation {
                  changeCustomerPassword(
                    currentPassword: "User@123"
                    newPassword: "User@123"
                    currentPin: "345678"
                    newPin: "345678"
                  ) {
                    firstname
                    lastname
                    email
                  }
                }
            MUTATION;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['changeCustomerPassword']);
        $this->assertNotEmpty($response['changeCustomerPassword']);
        $response = $response['changeCustomerPassword'];
        $this->assertArrayHasKey('firstname', $response);
        $this->assertArrayHasKey('lastname', $response);
        $this->assertArrayHasKey('email', $response);
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
