<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Test\GraphQl;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

class UpdateCustomerTest extends GraphQlAbstract
{
    /**
     * Customer Update unit test case
     */
    public function testUpdateCustomer()
    {
        $query
            = <<<MUTATION
            mutation {
                updateBatCustomer (
                  input:{
                      outletId: "707671"
                  mobilenumber: "010 1919 1938"
                  secondary_email : "john@test.com"
                  name: "MuthuGF"
                  address: {
                      street: "Testt Street34"
                      city: "City2"
                      postcode: "123456"
                  }
                })
                {
                    customer{
                     email
                     firstname
                    }
                }
              }

MUTATION;
        $response = $this->graphQlMutation($query);
        $this->assertIsArray($response['updateBatCustomer']);
        $this->assertNotEmpty($response['updateBatCustomer']);
        $customerResponse = $response['updateBatCustomer']['customer'];
        $this->assertArrayHasKey('email', $customerResponse);
        $this->assertNotEmpty($customerResponse['email']);
        $this->assertArrayHasKey('firstname', $customerResponse);
        $this->assertNotEmpty($customerResponse['firstname']);
    }
}
