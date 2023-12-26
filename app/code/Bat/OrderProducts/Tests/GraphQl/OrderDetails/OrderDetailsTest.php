<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\OrderProducts\Tests\GraphQl\OrderDetails;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class OrderDetailsTest
 * Bat\OrderProducts\Tests\GraphQl\OrderDetails
 */
class OrderDetailsTest extends GraphQlAbstract
{
    /**
     * Test OrderDetails Function
     */
    public function testOrderDetails()
    {
        $query
            = <<<QUERY
            {
                orderDetails(order_id:3) {
                payment_deadline
                message
                order_id
                order_amount
                order_status
                order_date
                outlet_name
                outlet_owner_name
                address{
                    street{
                        street1
                        street2
                    }
                    city
                    region
                    postal
                }
                phone_number
                account_number
                account_holder
                bank_details{
                    bank_code
                    bank_name
                }
                }
                }
        
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['orderDetails']);
        $this->assertNotEmpty($response['orderDetails']);
        $orderDetails = $response['orderDetails'];
        $this->assertNotEmpty($orderDetails['address']);
        $this->assertNotEmpty($orderDetails['address']['street']);
        $addressDetails = $orderDetails['address'];
        $bankDetails = $orderDetails['bank_details'];
        $this->assertArrayHasKey('payment_deadline', $orderDetails);
        $this->assertNotEmpty($orderDetails['payment_deadline']);
        $this->assertArrayHasKey('message', $orderDetails);
        $this->assertNotEmpty($orderDetails['message']);
        $this->assertArrayHasKey('order_id', $orderDetails);
        $this->assertNotEmpty($orderDetails['order_id']);
        $this->assertArrayHasKey('order_amount', $orderDetails);
        $this->assertNotEmpty($orderDetails['order_amount']);
        $this->assertArrayHasKey('order_status', $orderDetails);
        $this->assertNotEmpty($orderDetails['order_status']);
        $this->assertArrayHasKey('order_date', $orderDetails);
        $this->assertNotEmpty($orderDetails['order_date']);
        $this->assertArrayHasKey('outlet_name', $orderDetails);
        $this->assertNotEmpty($orderDetails['outlet_name']);
        $this->assertArrayHasKey('outlet_owner_name', $orderDetails);
        $this->assertNotEmpty($orderDetails['outlet_owner_name']);
        $this->assertArrayHasKey('is_first_order', $orderDetails);
        $this->assertNotEmpty($orderDetails['is_first_order']);
        $this->assertArrayHasKey('street1', $addressDetails['street']);
        $this->assertNotEmpty($addressDetails['street']['street1']);
        $this->assertArrayHasKey('street2', $addressDetails['street']);
        $this->assertArrayHasKey('city', $addressDetails);
        $this->assertNotEmpty($addressDetails['city']);
        $this->assertArrayHasKey('region', $addressDetails);
        $this->assertNotEmpty($addressDetails['region']);
        $this->assertArrayHasKey('postal', $addressDetails);
        $this->assertNotEmpty($addressDetails['postal']);
        $this->assertArrayHasKey('phone_number', $orderDetails);
        $this->assertNotEmpty($orderDetails['phone_number']);
        $this->assertArrayHasKey('bank_code', $bankDetails);
        $this->assertNotEmpty($bankDetails['bank_code']);
        $this->assertArrayHasKey('bank_name', $bankDetails);
        $this->assertNotEmpty($bankDetails['bank_name']);
        $this->assertArrayHasKey('account_number', $orderDetails);
        $this->assertNotEmpty($orderDetails['account_number']);
        $this->assertArrayHasKey('account_holder', $orderDetails);
        $this->assertNotEmpty($orderDetails['account_holder']);
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
