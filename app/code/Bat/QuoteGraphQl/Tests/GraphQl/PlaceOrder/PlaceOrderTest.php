<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\QuoteGraphQl\Tests\GraphQl\PlaceOrder;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

/**
 * @Class PlaceOrderTest
 * Test Case Place order
 */
class PlaceOrderTest extends GraphQlAbstract
{
    /**
     * @throws \Exception
     *
     * Place order Cart ID test case
     */
    public function testPlaceOrderCartId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Required parameter "cart_id" is missing');
        $query = <<<QUERY
        mutation {
          placeOrder(
            input: {
              cart_id: ""
              order_consent: true
            }
          ) {
            order {
              order_number
            }
          }
        }
        QUERY;
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * @throws \Exception
     *
     * Place order - cart ID Does not exist  test case
     */
    public function testPlaceOrderCartIdDoesNotExist()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not find a cart with ID "test"');
        $query = <<<QUERY
        mutation {
          placeOrder(
            input: {
              cart_id: "test"
              order_consent: true
            }
          ) {
            order {
              order_number
            }
          }
        }
        QUERY;
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * @throws \Exception
     *
     * Place order - order consent  test case
     */
    public function testPlaceOrderOrderConsent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must accept the terms and conditions of service to place order');
        $query = <<<QUERY
        mutation {
          placeOrder(
            input: {
              cart_id: "test"
              order_consent: false
            }
          ) {
            order {
              order_number
            }
          }
        }
        QUERY;
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = 'test@gmail.com'): array
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
