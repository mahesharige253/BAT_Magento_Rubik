<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Tests\GraphQl;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class CustomerIsMobileAvailableTest
 * Bat\CustomerGraphQl\Tests\GraphQl
 */
class CheckStatusTest extends GraphQlAbstract
{
    public function testCustomerFound()
    {
        $query
        = <<<QUERY
        {
            getCustomerApplicationStatus(mobilenumber: "010 1234 1220")
            {
                outlet_id
                heading
                message
                call_center_number
                rejected_fields
                customer {
                    firstname
                    business_license_file
                    outlet_name
                }
            }
        }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['getCustomerApplicationStatus']);
        $this->assertNotEmpty($response['getCustomerApplicationStatus']);
        $isMobileAvailable = $response['getCustomerApplicationStatus'];
        foreach($isMobileAvailable as $customerData) {
            $this->assertArrayHasKey('heading', $customerData);
            $this->assertNotNull($customerData['heading']);
            $this->assertArrayHasKey('message', $customerData);
            $this->assertNotNull($customerData['message']);
        }
    }

    public function testCustomerNotFound()
    {
        $query
        = <<<QUERY
        {
            getCustomerApplicationStatus(mobilenumber: "010 1234 1220")
            {
                outlet_id
                heading
                message
                call_center_number
                rejected_fields
                customer {
                    firstname
                    business_license_file
                    outlet_name
                }
            }
        }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['getCustomerApplicationStatus']);
        $this->assertNotEmpty($response['getCustomerApplicationStatus']);
        $isMobileAvailable = $response['getCustomerApplicationStatus'];
        foreach($isMobileAvailable as $customerData) {
            $this->assertArrayHasKey('heading', $customerData);
            $this->assertNotNull($customerData['heading']);
            $this->assertArrayHasKey('message', $customerData);
        }
    }
}
