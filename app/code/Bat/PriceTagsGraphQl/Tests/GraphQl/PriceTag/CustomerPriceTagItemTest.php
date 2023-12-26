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
 * Class CustomerPriceTagItemTest
 * Bat\PriceTagsGraphQl\Tests\GraphQl\PriceTag
 */
class CustomerPriceTagItemTest extends GraphQlAbstract
{
    /**
     * Get cart price tag list
     */
    public function testCartPriceTagList()
    {
         $query
        = <<<QUERY
            {
            cartPriceTagList
                {
                    priceTagImage
                    priceTagName
                    priceTagSku
                }
            }
        QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['cartPriceTagList']);
        $this->assertNotEmpty($response['cartPriceTagList']);
        $cartPriceTagList = $response['cartPriceTagList'];
        foreach ($cartPriceTagList as $item) {
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
    private function getHeaderMap(string $username = 'testraj@gmail.com'): array
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
