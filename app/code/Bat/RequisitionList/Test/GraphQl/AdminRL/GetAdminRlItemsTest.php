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
 * Class GetAdminRlItemsTest
 * Bat\RequisitionList\Tests\GraphQl\AdminRL
 */
class GetAdminRlItemsTest extends GraphQlAbstract
{
    public function testAdminRlData()
    {
        $query
            = <<<QUERY
        {
            customer{
                adminRequisitionItems(requisition_list_id: 4){
                    adminitemsdata{
                        name
                        sku
                        image
                        default_attribute
                        price_range{
                            maximum_price{
                                final_price{
                                    currency
                                    value
                                }
                            }
                        }
                    }
                    quantity
                    subtotal
                    }
                }
            }
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customer']);
        $this->assertIsArray($response['customer']['adminRequisitionItems']);
        $adminRlDatas = $response['customer']['adminRequisitionItems'];
        foreach ($adminRlDatas as $adminRlData) {
            $this->assertArrayHasKey('name', $adminRlData['adminitemsdata']);
            $this->assertNotEmpty($adminRlData['adminitemsdata']['name']);
            $this->assertArrayHasKey('sku', $adminRlData['adminitemsdata']);
            $this->assertNotEmpty($adminRlData['adminitemsdata']['sku']);
            $this->assertArrayHasKey('image', $adminRlData['adminitemsdata']);
            $this->assertArrayHasKey('default_attribute', $adminRlData['adminitemsdata']);
            $this->assertArrayHasKey('quantity', $adminRlData);
            $this->assertArrayHasKey('subtotal', $adminRlData);
            $this->assertArrayHasKey('currency', $adminRlData['adminitemsdata']
            ['price_range']['maximum_price']['final_price']);
            $this->assertArrayHasKey('value', $adminRlData['adminitemsdata']
            ['price_range']['maximum_price']['final_price']);
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
