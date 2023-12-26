<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\VirtualBank\Tests\GraphQl\Vba;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

/**
 * @Class VbaBanksTest
 * Test Case for Virtual Banks
 */
class VbaBanksTest extends GraphQlAbstract
{
    /**
     * @throws \Exception
     * Get Virtual Banks
     */
    public function testGetVirtualBanks()
    {
        $query
            = <<<QUERY
            {
                getVirtualBanks{
                    bank_details{
                        bank_name
                        bank_code
                    }
                }
            }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['getVirtualBanks']);
        $this->assertArrayHasKey('bank_details', $response['getVirtualBanks']);
        $this->assertIsArray($response['getVirtualBanks']['bank_details']);
    }
}
