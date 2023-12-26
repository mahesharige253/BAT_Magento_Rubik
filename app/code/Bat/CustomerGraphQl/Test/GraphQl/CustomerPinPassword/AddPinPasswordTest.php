<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Tests\GraphQl\CustomerPinPassword;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class AddPinPasswordTest
 * Bat\CustomerGraphQl\Tests\GraphQl\CustomerPinPassword
 */
class AddPinPasswordTest extends GraphQlAbstract
{
  
    public function testLogin()
    {
        $query
        = <<<MUTATION
            mutation {
                setCustomerPinPassword(
                input:{
                    outletId: "1234567890"
                    password: "rajan@321"
                    pin: "123456"
                }
                ){              
                    success
                    message
                }
            }
    MUTATION;
        $response = $this->graphQlMutation($query);
        $this->assertIsArray($response['setCustomerPinPassword']);
        $this->assertNotEmpty($response['setCustomerPinPassword']);
        $customerPinPassword = $response['setCustomerPinPassword'];
        $this->assertArrayHasKey('success', $customerPinPassword);
        $this->assertNotEmpty($customerPinPassword['success']);
        $this->assertArrayHasKey('message', $customerPinPassword);
        $this->assertNotEmpty($customerPinPassword['message']);
    }
}
