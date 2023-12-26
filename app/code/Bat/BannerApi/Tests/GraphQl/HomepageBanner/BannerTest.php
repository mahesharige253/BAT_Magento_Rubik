<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BannerApi\Tests\GraphQl\HomepageBanner;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class BannerTest
 * Bat\BannerApi\Tests\GraphQl\HomepageBanner
 */
class BannerTest extends GraphQlAbstract
{
    public function testBannerData()
    {
        $query
        = <<<QUERY
        {
          bannerData{
            banner_title
            image_name
            button_title
            url_key
            position
          }
        }
QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['bannerData']);
        $this->assertNotEmpty($response['bannerData']);
        $banners = $response['bannerData'];
        foreach ($banners as $banner) {
            $this->assertArrayHasKey('banner_title', $banner);
            $this->assertNotEmpty($banner['banner_title']);
            $this->assertArrayHasKey('image_name', $banner);
            $this->assertNotEmpty($banner['image_name']);
            $this->assertArrayHasKey('button_title', $banner);
            $this->assertArrayHasKey('url_key', $banner);
            $this->assertArrayHasKey('position', $banner);
            $this->assertNotEmpty($banner['position']);
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
        $customerId = (int)$customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
