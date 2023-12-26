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
 * Class RemoveBulkCartItemTest
 * Bat\BulkOrder\Tests\GraphQl\BulkOrder\RemoveBulkCartItemTest
 */
class RemoveBulkCartItemTest extends GraphQlAbstract
{
    /**
     * Remove Bulk Order Store
     */
    public function testRemoveBulkOrderStore()
    {
        $mutation
            = <<<MUTATION
            mutation {
  removeBulkOrderCartItem(
    input: {
      masked_cart_id: "jxRIzK282Mcw7Aas14LgsDgUjbz6Q55y",
      cart_item_id:651
    }
  ) {
      success
      message
  }
}
MUTATION;

        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['removeBulkOrderCartItem']);
        $this->assertArrayHasKey('message', $response['removeBulkOrderCartItem']);
        $this->assertNotEmpty($response['removeBulkOrderCartItem']['message']);
        $this->assertArrayHasKey('success', $response['removeBulkOrderCartItem']);
        $this->assertNotEmpty($response['removeBulkOrderCartItem']['success']);
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
