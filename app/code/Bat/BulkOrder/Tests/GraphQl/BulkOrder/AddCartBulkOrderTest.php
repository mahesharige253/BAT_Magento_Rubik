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
 * Class AddCartBulkOrderTest
 * Bat\BulkOrder\Tests\GraphQl\BulkOrder\AddCartBulkOrderTest
 */
class AddCartBulkOrderTest extends GraphQlAbstract
{
    /**
     * Add CartBulk Order
     */
    public function testAddCartBulkOrder()
    {
        $mutation
            = <<<MUTATION
            mutation{
                addBulkOrderCartItem(input: {
                    masked_cart_id:"oBWvSMvzO5Syb23B5l0hXoYBkW0V3Y9P",
                    quantity: 20,
                    sku:"Test TM dunhill-6mg"
                })
                {
                    message
                    success
                }
            }
MUTATION;

        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['addBulkOrderCartItem']);
        $this->assertArrayHasKey('message', $response['addBulkOrderCartItem']);
        $this->assertNotEmpty($response['addBulkOrderCartItem']['message']);
        $this->assertArrayHasKey('success', $response['addBulkOrderCartItem']);
        $this->assertNotEmpty($response['addBulkOrderCartItem']['success']);
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
