<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\GraphQl\DecryptData;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Bat\CustomerGraphQl\Helper\Data;
use PHPUnit\Framework\TestResult;

class DecryptApiTest extends GraphQlAbstract
{
    /**
     * Decrypt data unit test case
     */
    public function testDecryptData()
    {
        $mutation
            = <<<MUTATION
            mutation{
                decryptData(input: {
                    encrypted_data: "Ctlbw/k6ba+qDR22aIYONA=="
                }){
                     decrypted_data
                     success
                }
            }

MUTATION;
        $response = $this->graphQlMutation($mutation, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['decryptData']);
        $this->assertNotEmpty($response['decryptData']);
        $decryptDataResponse = $response['decryptData'];
        $this->assertArrayHasKey('decrypted_data', $decryptDataResponse);
        $this->assertArrayHasKey('success', $decryptDataResponse);
        $this->assertNotEmpty($decryptDataResponse['success']);
    }

   /**
    * Retrieve customer Email
    *
    * @return string
    */
    private function getCustomerUsername()
    {
        $objectManager = Bootstrap::getObjectManager();
        $dataRepository = $objectManager->get(Data::class);
        return $dataRepository->getCustomerEmail();
    }

/**
 * Retrieve customer authorization headers
 *
 * @return array
 * @throws AuthenticationException
 */
    private function getHeaderMap(): array
    {
        $customerEmail = $this->getCustomerUsername();
        $objectManager = Bootstrap::getObjectManager();
        $customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $customerData = $customerRepository->get($customerEmail);
        $customerId = (int) $customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
