<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\GraphQl\CustomerPinPassword;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class IsValidTokenTest
 * Bat\CustomerGraphQl\Test\GraphQl\CustomerPinPassword
 */
class IsValidTokenTest extends GraphQlAbstract
{
    /**
     * Test Whether Token Valid or not
     */
    public function testisTokenValid()
    {
        $query
            = <<<QUERY
        {
            isTokenValid(generateNewToken: true){
                is_token_valid
                token
            }
        }
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['isTokenValid']);
        $this->assertNotEmpty($response['isTokenValid']);
        $validTokenData = $response['isTokenValid'];
        $this->assertArrayHasKey('is_token_valid', $validTokenData);
        $this->assertNotNull($validTokenData['is_token_valid']);
        $this->assertArrayHasKey('token', $validTokenData);
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
