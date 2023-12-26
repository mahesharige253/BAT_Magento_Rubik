<?php
declare(strict_types=1);
namespace Bat\CustomerAddressGraphQl\Tests\GraphQl\Resolver;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class ShippingAdditionalDetails
 * Bat\CustomerAddressGraphQl\Tests\GraphQl\Resolver
 */
class ShippingAdditionalDetails extends GraphQlAbstract
{
    
    /**
     * @throws \Exception
     * Get PriceTagList
     */
    public function testPriceTagList()
    {
        $query = <<<QUERY
        {
        customerCart {
        id
        items {
        id
        uid
        product {
        name
        sku
        image
        price
        default_attribute
        }
        quantity
        }
        total_quantity
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
        applied_store_credit{
        applied_balance {
        value
        }
        current_balance{
        value
        }
        enabled
        }
        non_credit_customer{
        overpayment
        }
        credit_customer{
        remaining_ar
        overpayment
        minimum_payment
        }
        shipping_addresses{
        firstname
        lastname
        company
        street
        region {
        code
        label
        }
        city
        postcode
        telephone
        country {
        code
        label
        }
        }
        shipping_additional_details{
        outlet_name
        outlet_owner_name
        phone_number
        }
        }
        }
        QUERY;
        $response = $this->graphQlQuery($query, [], '', $this->getHeaderMap());
        $this->assertIsArray($response['customerCart']);
        $this->assertNotEmpty($response['customerCart']);
        $address = $response['customerCart'];
        $shippingAddress = $address['shipping_addresses'];
        foreach ($shippingAddress as $item) {
            $this->assertArrayHasKey('firstname', $item);
            $this->assertNotEmpty($item['firstname']);
            $this->assertArrayHasKey('lastname', $item);
            $this->assertNotEmpty($item['lastname']);
            if (!empty($item['company'])) {
                $this->assertArrayHasKey($item['company']);
            }
            $this->assertArrayHasKey('street', $item);
            $this->assertNotEmpty($item['street']);
            $this->assertArrayHasKey('region', $item);
            $this->assertNotEmpty($item['region']);
            $this->assertArrayHasKey('city', $item);
            $this->assertNotEmpty($item['city']);
            $this->assertArrayHasKey('postcode', $item);
            $this->assertNotEmpty($item['postcode']);
            $this->assertArrayHasKey('telephone', $item);
            $this->assertNotEmpty($item['telephone']);
            $this->assertArrayHasKey('country', $item);
            $this->assertNotEmpty($item['country']);
        }

        $shippingAdditionalDetails = $address['shipping_additional_details'];
        $this->assertArrayHasKey('outlet_name', $shippingAdditionalDetails);
        $this->assertNotEmpty($shippingAdditionalDetails['outlet_name']);
        $this->assertArrayHasKey('outlet_owner_name', $shippingAdditionalDetails);
        if (!empty($shippingAdditionalDetails['outlet_owner_name'])) {
            $this->assertNotEmpty($shippingAdditionalDetails['outlet_owner_name']);
        }
        $this->assertArrayHasKey('phone_number', $shippingAdditionalDetails);
        $this->assertNotEmpty($shippingAdditionalDetails['phone_number']);
    }

     /**
      * Retrieve customer authorization headers
      *
      * @param string $username
      * @return array
      * @throws AuthenticationException
      */
    private function getHeaderMap(string $username = 'rajan.yadav@embitel.com'): array
    {
        $objectManager = Bootstrap::getObjectManager();
        $CustomerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $customerData = $CustomerRepository->get($username);
        $customerId = (int)$customerData->getId();
        $customerTokenService = $objectManager->get(TokenFactory::class);
        $customerToken = $customerTokenService->create();
        $customerTokenVal = $customerToken->createCustomerToken($customerId)->getToken();
        $headerMap = ['Authorization' => 'Bearer ' . $customerTokenVal];
        return $headerMap;
    }
}
