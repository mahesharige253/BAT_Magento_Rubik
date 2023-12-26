<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerConsentForm\Tests\GraphQl\ConsentForm;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Class GetConsentDataTest
 * Bat\CustomerConsentForm\Tests\GraphQl\ConsentForm
 */
class GetConsentDataTest extends GraphQlAbstract
{
  /**
   * Get Consent Form Data
   */
    public function testConsentFormData()
    {
        $query
        = <<<QUERY
        { 
          consentData(input:{
              consent_type: "Bulkorder_checkout"
          }){ 
          consent_title 
          identifier 
          consent_required 
          link_status 
          content 
          position 
          validate_message 
          } 
          }

QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['consentData']);
        $this->assertNotEmpty($response['consentData']);
        $consentData = $response['consentData'];
        foreach ($consentData as $consent) {
            $this->assertArrayHasKey('consent_title', $consent);
            $this->assertNotEmpty($consent['consent_title']);
            $this->assertArrayHasKey('identifier', $consent);
            $this->assertNotEmpty($consent['consent_required']);
            $this->assertArrayHasKey('consent_required', $consent);
            $this->assertArrayHasKey('link_status', $consent);
            $this->assertNotEmpty($consent['link_status']);
            $this->assertArrayHasKey('content', $consent);
            $this->assertArrayHasKey('position', $consent);
            $this->assertArrayHasKey('validate_message', $consent);
        }
    }
}
