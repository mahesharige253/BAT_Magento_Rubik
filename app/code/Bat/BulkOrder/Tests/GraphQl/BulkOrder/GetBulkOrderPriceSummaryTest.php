<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BulkOrder\Tests\GraphQl\BulkOrder;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;

/**
 * Class GetBulkOrderPriceSummaryTest
 * Bat\BulkOrder\Tests\GraphQl\BulkOrder\GetBulkOrderPriceSummaryTest
 */
class GetBulkOrderPriceSummaryTest extends GraphQlAbstract
{
    /**
     * Remove Bulk Order Store
     */
    public function testGetBulkOrderDetails()
    {
        $query
            = <<<QUERY
            {
    getBulkOrderDetails(input: {parent_outlet: "310510"})
    {
   outletDetails{
       total_stores
       total_qty
       subtotal
       overpayment
       remaining_ar
       total
       minimum_payment
   }
    cartDetails{
        is_active
        is_credit_customer
        is_first_order
        masked_cart_id
        outlet_id
        cartItemsCount
        outletName
        cartData{
        billing_address {
      city
      country {
        code
        label
      }
      firstname
      lastname
      postcode
      region {
        code
        label
      }
      street
      telephone
    }
    shipping_addresses {
      firstname
      lastname
      street
      city
      region {
        code
        label
      }
      country {
        code
        label
      }
    }
    total_quantity
    items {
      id
      quantity
      is_price_tag
      product {
        name
        sku
        default_attribute
        price_range{
            maximum_price{
                final_price{
                    currency
                    value
                }
            }
        }
      }
      quantity
    }
        prices{
      subtotal_excluding_tax{
        value
      }
      subtotal_including_tax{
         value
      }
      grand_total{
        value
      }
         }
        }
        overpayment
        remaining_ar
        minimum_payment
    }
}
}
QUERY;

        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['getBulkOrderDetails']);
        $this->assertIsArray($response['getBulkOrderDetails']['outletDetails']);
        $this->assertIsArray($response['getBulkOrderDetails']['cartDetails']);
        $outletDetails = $response['getBulkOrderDetails']['outletDetails'];
        $cartDetails = $response['getBulkOrderDetails']['cartDetails'];
        $this->assertArrayHasKey('total_qty', $outletDetails);
        $this->assertNotEmpty($outletDetails['total_qty']);
        $this->assertArrayHasKey('subtotal', $outletDetails);
        $this->assertNotEmpty($outletDetails['subtotal']);
        $this->assertArrayHasKey('overpayment', $outletDetails);
        $this->assertArrayHasKey('remaining_ar', $outletDetails);
        $this->assertArrayHasKey('total', $outletDetails);
        $this->assertArrayHasKey('minimum_payment', $outletDetails);
        
        foreach ($cartDetails as $cartDetail) {
            $this->assertArrayHasKey('overpayment', $cartDetail);
            $this->assertArrayHasKey('remaining_ar', $cartDetail);
            $this->assertArrayHasKey('minimum_payment', $cartDetail);
            
        }
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = '310510@310510.com'): array
    {
        $objectManager = Bootstrap::getObjectManager();
        $CustomerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $customerData = $CustomerRepository->get($username);
        $customerId = (int) $customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
