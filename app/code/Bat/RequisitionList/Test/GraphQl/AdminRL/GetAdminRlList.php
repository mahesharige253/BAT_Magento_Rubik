<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\RequisitionList\Tests\GraphQl\AdminRL;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class GetAdminRlList
 * Bat\RequisitionList\Tests\GraphQl\AdminRL
 */
class GetAdminRlList extends GraphQlAbstract
{
    public function testAdminRlItems()
    {
        $query
            = <<<QUERY
            {
                customer{
                requisition_lists{
                    items{
                        name
                        uid
                        items{
                            items{
                                uid
                                product{
                                    uid
                                    name
                                    quantity
                                }
                            }
                        }
                        items_count
                    }
                    total_count
                    max_limit
                }
                admin_requisition_lists{
                   items{
                       uid
                       name
                       product_count
                       bestseller
                       first_product_name
                       product_count
                   }
                   total{
                       admin_max_limit
                       total_rl_count
                   }
                    
                }
            }
            }
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customer']);
        $this->assertIsArray($response['customer']['requisition_lists']);
        $this->assertIsArray($response['customer']['admin_requisition_lists']);
        $customerRlItems = $response['customer']['requisition_lists']['items'];
        $adminRlItems = $response['customer']['admin_requisition_lists']['items'];
        $this->assertArrayHasKey('total_count', $response['customer']['requisition_lists']);
        $this->assertArrayHasKey('max_limit', $response['customer']['requisition_lists']);
        $this->assertArrayHasKey('admin_max_limit', $response['customer']['admin_requisition_lists']['total']);
        $this->assertArrayHasKey('total_rl_count', $response['customer']['admin_requisition_lists']['total']);

        foreach ($customerRlItems as $customerRlItem) {
            $this->assertArrayHasKey('name', $customerRlItem);
            $this->assertNotEmpty($customerRlItem['name']);
            $this->assertArrayHasKey('uid', $customerRlItem);
            $this->assertNotEmpty($customerRlItem['uid']);
            $this->assertArrayHasKey('items_count', $customerRlItem);
            foreach ($customerRlItem['items']['items'] as $items) {
                $this->assertArrayHasKey('uid', $items);
                $this->assertNotEmpty($items);
                $this->assertArrayHasKey('uid', $items['product']);
                $this->assertNotEmpty($items['product']);
                $this->assertArrayHasKey('name', $items['product']);
                $this->assertNotEmpty($items['product']);
                $this->assertArrayHasKey('quantity', $items['product']);
                $this->assertNotEmpty($items['product']);
            }
        }
        foreach ($adminRlItems as $adminRlItem) {
            $this->assertArrayHasKey('uid', $adminRlItem);
            $this->assertNotEmpty($adminRlItem['uid']);
            $this->assertArrayHasKey('name', $adminRlItem);
            $this->assertNotEmpty($adminRlItem['name']);
            $this->assertArrayHasKey('product_count', $adminRlItem);
            $this->assertArrayHasKey('bestseller', $adminRlItem);
            $this->assertArrayHasKey('first_product_name', $adminRlItem);
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
