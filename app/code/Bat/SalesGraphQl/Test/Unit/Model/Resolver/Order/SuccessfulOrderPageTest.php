<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\SalesGraphQl\Test\Unit\Model\Resolver\Order;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

/**
 * Class SuccessfulOrderPageTest
 *  Bat\SalesGraphQl\Test\Unit\Model\Resolver\Order\SuccessfulOrderPageTest
 */
class SuccessfulOrderPageTest extends GraphQlAbstract
{
    /**
     * Get OrderDetails on Thankyou Page
     */
    public function testSuccessfulOrderPageDetails()
    {
        $query
            = <<<QUERY
            {
                customer {
                parentOrders(
                filter: {
                number: {
                eq: "200000006"
                }
                }) {    
                items {
                bulk_order_id
                payment_deadline_date
                message
                order_amount{
                    net
                    vat
                    total
                }
                parent_order_quantity{
                    outlet_count
                    items_count
                    items_quantity
                }
                virtual_bank_account{
                    bank_name
                    account_number
                    account_holder_name
                }
                child_order_list {
                    id
                    outlet_name
                    increment_id
                    childItems_count
                    total_qty
                    grand_total
                    overpayment
                    remaining_ar
                    minimum_amount
                    subtotal
                    is_first_order
                    items{
                        title
                        price
                        quantity
                        default_attribute
                        subtotal
                    }
                    virtual_bank_account{
                        account_holder_name
                        account_number
                        bank_name
                    }
                    shipping_addresses{
                        firstname
                        lastname
                        city
                        street
                        postcode
                        region
                        telephone
                        country{
                            code
                            label
                        }
                    }
                }
                }
                }
                }
                }
QUERY;

        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customer']);
        $this->assertIsArray($response['customer']['parentOrders']);
        $this->assertIsArray($response['customer']['parentOrders']['items']);
        $parentVba = $response['customer']['parentOrders']['items']['virtual_bank_account'];
        $this->assertArrayHasKey('bank_name', $parentVba);
        $this->assertNotEmpty($parentVba['bank_name']);
        $this->assertArrayHasKey('account_number', $parentVba);
        $this->assertNotEmpty($parentVba['account_number']);
        $this->assertArrayHasKey('account_holder_name', $parentVba);
        $this->assertNotEmpty($parentVba['account_holder_name']);
        $childOrderDetails = $response['customer']['parentOrders']['items']['child_order_list'];
        foreach ($childOrderDetails as $childOrderDetail) {
            $this->assertArrayHasKey('id', $childOrderDetail);
            $this->assertNotEmpty($childOrderDetail['id']);
            $this->assertArrayHasKey('outlet_name', $childOrderDetail);
            $this->assertNotEmpty($childOrderDetail['outlet_name']);
            $this->assertArrayHasKey('increment_id', $childOrderDetail);
            $this->assertNotEmpty($childOrderDetail['increment_id']);
            $this->assertArrayHasKey('childItems_count', $childOrderDetail);
            $this->assertArrayHasKey('total_qty', $childOrderDetail);
            $this->assertArrayHasKey('grand_total', $childOrderDetail);
            $this->assertArrayHasKey('overpayment', $childOrderDetail);
            $this->assertArrayHasKey('remaining_ar', $childOrderDetail);
            $this->assertArrayHasKey('minimum_amount', $childOrderDetail);
            $this->assertArrayHasKey('subtotal', $childOrderDetail);
            $this->assertArrayHasKey('is_first_order', $childOrderDetail);
            $this->assertArrayHasKey('account_holder_name', $childOrderDetail['virtual_bank_account']);
            $this->assertArrayHasKey('account_number', $childOrderDetail['virtual_bank_account']);
            $this->assertArrayHasKey('bank_name', $childOrderDetail['virtual_bank_account']);
            $this->assertArrayHasKey('firstname', $childOrderDetail['shipping_addresses']);
            $this->assertArrayHasKey('lastname', $childOrderDetail['shipping_addresses']);
            $this->assertArrayHasKey('city', $childOrderDetail['shipping_addresses']);
            $this->assertArrayHasKey('street', $childOrderDetail['shipping_addresses']);
            $this->assertArrayHasKey('postcode', $childOrderDetail['shipping_addresses']);
            $this->assertArrayHasKey('region', $childOrderDetail['shipping_addresses']);
            $this->assertArrayHasKey('telephone', $childOrderDetail['shipping_addresses']);
            $this->assertArrayHasKey('code', $childOrderDetail['shipping_addresses']['country']);
            $this->assertArrayHasKey('label', $childOrderDetail['shipping_addresses']['country']);
            $chilItemDetails = $childOrderDetail['items'];
            foreach ($chilItemDetails as $childItemDetail) {
                $this->assertArrayHasKey('title', $childItemDetail);
                $this->assertNotEmpty($childItemDetail['title']);
                $this->assertArrayHasKey('price', $childItemDetail);
                $this->assertArrayHasKey('quantity', $childItemDetail);
                $this->assertArrayHasKey('default_attribute', $childItemDetail);
                $this->assertArrayHasKey('subtotal', $childItemDetail);
            }
        }
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
