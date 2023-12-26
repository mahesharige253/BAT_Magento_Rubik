<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Tests\GraphQl\CustomerPinPassword;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class LoginWithPinPasswordTest
 * Bat\CustomerGraphQl\Tests\GraphQl\CustomerPinPassword
 */
class LoginWithPinPasswordTest extends GraphQlAbstract
{

    public function testLogin()
    {
        $query
            = <<<MUTATION
            mutation {
                loginWithPinOrPassword(
                input:{
                outletId: "1234567890"
                pin: "123456"
                }
                ){
                token
                }
                }
MUTATION;
        $response = $this->graphQlMutation($query);
        $this->assertIsArray($response['loginWithPinOrPassword']);
        $this->assertNotEmpty($response['loginWithPinOrPassword']);
        $customerLogin = $response['loginWithPinOrPassword'];
        $this->assertArrayHasKey('token', $customerLogin);
        $this->assertNotEmpty($customerLogin, 'token');
    }
}
