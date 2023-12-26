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
 * Class GetPriceTagListTest
 * Bat\PriceTagsGraphQl\Tests\GraphQl\PriceTag
 */
class PriceTagListTest extends GraphQlAbstract
{

    /**
     * @throws \Exception
     * Get PriceTagList
     */
    public function testPriceTagList()
    {
        $query
        = <<<QUERY
            {
            priceTagList
                {
                    priceTagImage
                    priceTagName
                    priceTagSku
                }
            }
        QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['priceTagList']);
        $this->assertNotEmpty($response['priceTagList']);
        $priceTagList = $response['priceTagList'];
        foreach ($priceTagList as $item) {
            $this->assertArrayHasKey('priceTagImage', $item);
            $this->assertNotEmpty($item['priceTagImage']);
            $this->assertArrayHasKey('priceTagName', $item);
            $this->assertNotEmpty($item['priceTagName']);
            $this->assertArrayHasKey('priceTagSku', $item);
            $this->assertNotEmpty($item['priceTagSku']);
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
