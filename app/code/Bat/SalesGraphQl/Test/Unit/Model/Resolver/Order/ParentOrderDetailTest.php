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
 * Class Dispaly Order List
 *
 */
class ParentOrderDetailTest extends GraphQlAbstract
{
    /**
     * Get Parent Order Detail
     */
    public function testParentOrderDetail()
    {
        $query
            = <<<QUERY
{
customer {
firstname
lastname
parentOrders(
filter: {
number: {
eq: "200000003"
}
}) {    
items {
order_date
order_type
bulk_order_id
items_count
payment_deadline_date
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
    increment_id
    order_type
    order_date
    status
    item_name
    grand_total
}
total {
    subtotal
    remaining_ar
    overpayment
    minimum_amount
    grand_total
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
        $parentOrder = $response['customer'];
            $this->assertArrayHasKey('firstname', $parentOrder);
            $this->assertArrayHasKey('items', $parentOrder['parentOrders']);
            $this->assertArrayHasKey('bulk_order_id', $parentOrder['parentOrders']['items']);
            $this->assertArrayHasKey('order_amount', $parentOrder['parentOrders']['items']);
            $this->assertArrayHasKey('parent_order_quantity', $parentOrder['parentOrders']['items']);
            $this->assertArrayHasKey('virtual_bank_account', $parentOrder['parentOrders']['items']);
            $this->assertArrayHasKey('child_order_list', $parentOrder['parentOrders']['items']);
        foreach ($parentOrder['parentOrders']['items']['child_order_list'] as $key => $childOrderList) {
            $this->assertArrayHasKey('id', $childOrderList);
            $this->assertArrayHasKey('increment_id', $childOrderList);
            $this->assertArrayHasKey('order_type', $childOrderList);
            $this->assertArrayHasKey('order_date', $childOrderList);
            $this->assertArrayHasKey('status', $childOrderList);
            $this->assertArrayHasKey('item_name', $childOrderList);
            $this->assertArrayHasKey('grand_total', $childOrderList);
        }
            $this->assertArrayHasKey('total', $parentOrder['parentOrders']['items']);
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
