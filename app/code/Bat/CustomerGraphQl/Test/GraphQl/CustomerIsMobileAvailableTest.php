<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Tests\GraphQl\CustomerPinPassword;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class CustomerIsMobileAvailableTest
 * Bat\CustomerGraphQl\Tests\GraphQl
 */
class CustomerIsMobileAvailableTest extends GraphQlAbstract
{
    public function testIsMobileNumberNotAvailable()
    {
        $query
        = <<<QUERY
        {
          isMobileAvailable(mobilenumber: "010 1919 1919") {
            is_mobile_available
            message
          }
        }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['isMobileAvailable']);
        $this->assertNotEmpty($response['isMobileAvailable']);
        $isMobileAvailable = $response['isMobileAvailable'];
        $this->assertArrayHasKey('is_mobile_available', $isMobileAvailable);
        $this->assertNotNull($isMobileAvailable['is_mobile_available']);
        $this->assertArrayHasKey('message', $isMobileAvailable);
        $this->assertNotNull($isMobileAvailable['message']);
        $this->assertNotEmpty($isMobileAvailable['message']);
    }

    public function testIsMobileNumberAvailable()
    {
        $query
        = <<<QUERY
        {
          isMobileAvailable(mobilenumber: "010 1234 1234") {
            is_mobile_available
            message
          }
        }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['isMobileAvailable']);
        $this->assertNotEmpty($response['isMobileAvailable']);
        $isMobileAvailable = $response['isMobileAvailable'];
        $this->assertArrayHasKey('is_mobile_available', $isMobileAvailable);
        $this->assertNotNull($isMobileAvailable['is_mobile_available']);
        $this->assertArrayHasKey('message', $isMobileAvailable);
        $this->assertNotNull($isMobileAvailable['message']);
    }

    public function testIsMobileNumberAvailableEmpty()
    {
        $query
        = <<<QUERY
        {
          isMobileAvailable(mobilenumber: "") {
            is_mobile_available
            message
          }
        }
        QUERY;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: Mobile number must be specified');
        $response = $this->graphQlQuery($query);
    }

    public function testIsMobileNumberNotValid()
    {
        $query
        = <<<QUERY
        {
          isMobileAvailable(mobilenumber: "1234567890") {
            is_mobile_available
            message
          }
        }
        QUERY;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: Mobile number value is not valid');
        $response = $this->graphQlQuery($query);
    }
}
