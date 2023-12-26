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
use Bat\CustomerGraphQl\Helper\Data;
use PHPUnit\Framework\TestResult;

/**
 * Class GetBulkOrderDetailsTest
 * Bat\BulkOrder\Tests\GraphQl\BulkOrder\GetBulkOrderDetailsTest
 */
class GetBulkOrderDetailsTest extends GraphQlAbstract
{
  
  /**
   * Remove Bulk Order Store
   */
    public function testGetBulkOrderDetails()
    {
        $query
        = <<<QUERY
            {
              getBulkOrderDetails(input: {parent_outlet: "329912"})
              {
             outletDetails{
                 total_stores
                 subtotal
                 overpayment
                 remaining_ar
                 total
                 minimum_payment
                 is_credit_customer
                 payment_deadline_date
                 vbaBulkDetails{
                     account_holder_name
                     account_number
                     bankDetails{
                         bank_code
                         bank_name
                     }
                 }
             }
              cartDetails{
                  masked_cart_id
                  vbaBulkDetails{
                      account_holder_name
                      account_number
                      bankDetails{
                          bank_code
                          bank_name
                      }
                  }
                  is_active
                  is_credit_customer
                  is_first_order
                  is_parent
                  cartItemsCount
                  cartItemsQty
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
                postcode
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
        $this->assertArrayHasKey('total_stores', $outletDetails);
        $this->assertArrayHasKey('subtotal', $outletDetails);
        $this->assertArrayHasKey('overpayment', $outletDetails);
        $this->assertArrayHasKey('remaining_ar', $outletDetails);
        $this->assertArrayHasKey('total', $outletDetails);
        $this->assertArrayHasKey('minimum_payment', $outletDetails);
        $this->assertArrayHasKey('is_credit_customer', $outletDetails);
        $this->assertArrayHasKey('payment_deadline_date', $outletDetails);
        $this->assertArrayHasKey('account_holder_name', $outletDetails['vbaBulkDetails']);
        $this->assertArrayHasKey('account_number', $outletDetails['vbaBulkDetails']);
        $this->assertArrayHasKey('bank_code', $outletDetails['vbaBulkDetails']['bankDetails']);
        $this->assertArrayHasKey('bank_name', $outletDetails['vbaBulkDetails']['bankDetails']);
        foreach ($cartDetails as $cartDetail) {
            $this->assertArrayHasKey('masked_cart_id', $cartDetail);
            $this->assertNotEmpty($cartDetail['masked_cart_id']);
            $this->assertArrayHasKey('is_active', $cartDetail);
            $this->assertArrayHasKey('account_holder_name', $cartDetail['vbaBulkDetails']);
            $this->assertNotEmpty($cartDetail['vbaBulkDetails']['account_holder_name']);
            $this->assertArrayHasKey('account_number', $cartDetail['vbaBulkDetails']);
            $this->assertNotEmpty($cartDetail['vbaBulkDetails']['account_number']);
            $this->assertArrayHasKey('bank_code', $cartDetail['vbaBulkDetails']['bankDetails']);
            $this->assertNotEmpty($cartDetail['vbaBulkDetails']['bankDetails']['bank_code']);
            $this->assertArrayHasKey('bank_name', $cartDetail['vbaBulkDetails']['bankDetails']);
            $this->assertNotEmpty($cartDetail['vbaBulkDetails']['bankDetails']['bank_name']);
            $this->assertNotEmpty($cartDetail['is_active']);
            $this->assertArrayHasKey('is_parent', $cartDetail);
            $this->assertArrayHasKey('cartItemsCount', $cartDetail);
            $this->assertArrayHasKey('cartItemsQty', $cartDetail);
            $this->assertArrayHasKey('outletName', $cartDetail);
            $this->assertNotEmpty($cartDetail['outletName']);
        }
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
        $CustomerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $customerData = $CustomerRepository->get($customerEmail);
        $customerId = (int) $customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
