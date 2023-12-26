<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\SalesGraphQl\Test\Unit\Model\Resolver\Order;

use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Display Order Detail
 *
 */
class OrderDetailTest extends GraphQlAbstract
{
    /**
     * Get Order Details
     */
    public function testOrderDetail()
    {
        $query
            = <<<QUERY
{
customer {
firstname
lastname
email
orders(
filter: {
number: {
eq: "000000001"
}
}) {    
items {
id
order_date
order_type
increment_id
status
items_count
items {
id
product_name
product_sku
is_price_tag
product_sale_price{
    value
    currency
}
product_image
default_attribute
product_type
quantity_ordered
quantity_invoiced
quantity_refunded
}
order_amount{
net
vat
total
}
total {
subtotal{
value
currency
}
total_tax{
value
}
remaining_ar
overpayment
minimum_amount
grand_total {
value
currency
}
}
payment_deadline_date
virtual_bank_account{
    bank_name
    account_number
    account_holder_name
}
delivery_details{
delivery_date
tracking_number
tracking_url
}
return_details{
return_date
tracking_number
tracking_url
}
shipping_address{
firstname
lastname
street
region
region_id
city
postcode
country_code
telephone
}
}
}
}
}
QUERY;
         $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customer']);
        /* This is not required as best seller might not have products. */
        $this->assertNotEmpty($response['customer']);
        $orders = $response['customer']['orders']['items'];
        foreach ($orders as $orderData) {
            $this->assertArrayHasKey('id', $orderData);
            $this->assertArrayHasKey('increment_id', $orderData);
            $this->assertArrayHasKey('order_type', $orderData);
            $this->assertArrayHasKey('order_date', $orderData);
            $this->assertArrayHasKey('status', $orderData);
            $this->assertArrayHasKey('order_amount', $orderData);
            $this->assertArrayHasKey('total', $orderData);
            $this->assertArrayHasKey('payment_deadline_date', $orderData);
            $this->assertArrayHasKey('virtual_bank_account', $orderData);
            $this->assertArrayHasKey('delivery_details', $orderData);
            $this->assertArrayHasKey('return_details', $orderData);
            $this->assertArrayHasKey('shipping_address', $orderData);
            foreach ($orderData['items'] as $itemDetail) {
                $this->assertArrayHasKey('product_name', $itemDetail);
                $this->assertArrayHasKey('product_sale_price', $itemDetail);
                $this->assertArrayHasKey('default_attribute', $itemDetail);
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
    private function getHeaderMap(string $username = '527155@527155.com'): array
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
