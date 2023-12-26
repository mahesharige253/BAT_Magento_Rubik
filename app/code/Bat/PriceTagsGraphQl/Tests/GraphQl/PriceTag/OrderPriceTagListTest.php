<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\PriceTagsGraphQl\Tests\GraphQl\PriceTag;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class OrderPriceTagListTest
 * Bat\PriceTagsGraphQl\Tests\GraphQl\PriceTag
 */
class OrderPriceTagListTest extends GraphQlAbstract
{

    /**
     * @throws \Exception
     * Get Order Price Tag List
     */
    public function testOrderPriceTagList()
    {
        $query
        = <<<QUERY
            {
            orderPriceTagList(orderId: 4) 
            {
            priceTagImage
            priceTagName
            }
        }
        QUERY;

        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['orderPriceTagList']);
        if (!empty($response['orderPriceTagList'])) {
            $this->assertNotEmpty($response['orderPriceTagList']);
            $orderPriceTagList = $response['orderPriceTagList'];
            foreach ($orderPriceTagList as $item) {
                $this->assertArrayHasKey('priceTagImage', $item);
                $this->assertNotEmpty($item['priceTagImage']);
                $this->assertArrayHasKey('priceTagName', $item);
                $this->assertNotEmpty($item['priceTagName']);
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
    private function getHeaderMap(string $username = 'rajan.yadav@embitel.com'): array
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
