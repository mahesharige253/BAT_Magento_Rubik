<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\GraphQl\AccountInfoUpdate;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class UpdateAccountConsentTest
 * Bat\CustomerGraphQl\Test\GraphQl\AccountInfoUpdate
 */
class UpdateAccountConsentTest extends GraphQlAbstract
{
  /**
   * Test Order Frequency data
   */
    public function testAccountConsent()
    {
        $mutation
        = <<<MUTATION
        mutation {
          updateAccountConsentInfo(input: {consent_identifier:"t&c,personal"}) {
              message
              success
            }
          }
          
MUTATION;
        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['updateAccountConsentInfo']);
        $this->assertNotEmpty($response['updateAccountConsentInfo']);
        $data = $response['updateAccountConsentInfo'];
        $this->assertArrayHasKey('message', $data);
        $this->assertNotNull($data['message']);
        $this->assertArrayHasKey('success', $data);
        $this->assertNotNull($data['success']);
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
