<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\Unit\Model\Resolver\Customer;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * @class GetCustomerDetailTest
 * Test case for get customer
 */
class GetCustomerDetailTest extends GraphQlAbstract
{
    /**
     * Get Customer Details Test case
     *
     * @throws \Exception
     */
    public function testCustomerData()
    {
        $query
            = <<<QUERY
                {
                  customer {
                    firstname
                    secondary_email
                    default_shipping
                    default_billing
                    email
                    outlet_name
                    outlet_id
                    mobilenumber
                    business_license_file
                    tobacco_license_file
                    business_license_number
                    tobacco_license_number
                    virtual_bank {
                        bank_details{
                            bank_name
                            bank_code
                        }
                      account_holder_name
                      account_number
                    }
                    addresses {
                      firstname
                      street
                      city
                      region {
                        region_code
                        region
                      }
                      postcode
                      vat_id
                      country_code
                      telephone
                      company
                      default_billing
                      default_shipping
                    }
                  }
                }
            QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customer']);
        $this->assertNotEmpty($response['customer']);
        $response = $response['customer'];
        $this->assertArrayHasKey('firstname', $response);
        $this->assertArrayHasKey('secondary_email', $response);
        $this->assertArrayHasKey('default_shipping', $response);
        $this->assertArrayHasKey('default_billing', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('mobilenumber', $response);
        $this->assertArrayHasKey('outlet_name', $response);
        $this->assertArrayHasKey('outlet_id', $response);
        $this->assertArrayHasKey('mobilenumber', $response);
        $this->assertArrayHasKey('business_license_file', $response);
        $this->assertArrayHasKey('tobacco_license_file', $response);
        $this->assertArrayHasKey('business_license_number', $response);
        $this->assertArrayHasKey('tobacco_license_number', $response);
        $this->assertArrayHasKey('virtual_bank', $response);
        $this->assertArrayHasKey('bank_details', $response['virtual_bank']);
        $this->assertArrayHasKey('bank_name', $response['virtual_bank']['bank_details']);
        $this->assertArrayHasKey('bank_code', $response['virtual_bank']['bank_details']);
        $this->assertArrayHasKey('account_holder_name', $response['virtual_bank']);
        $this->assertArrayHasKey('account_number', $response['virtual_bank']);
        $this->assertArrayHasKey('addresses', $response);
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
