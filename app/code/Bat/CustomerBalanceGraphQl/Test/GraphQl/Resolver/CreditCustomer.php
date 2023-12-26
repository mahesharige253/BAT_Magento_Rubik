<?php
declare(strict_types=1);
namespace Bat\CustomerBalanceGraphQl\Tests\GraphQl\Resolver;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class CreditCustomer
 * Bat\CustomerBalanceGraphQl\Tests\GraphQl\Resolver
 */
class CreditCustomer extends GraphQlAbstract
{

    /**
     * @throws \Exception
     * Get cart summary
     */
    public function testCreditCustomer()
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
        is_credit_customer
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
        $customerCart = $response['customerCart'];
        $this->assertArrayHasKey('prices', $customerCart);
        $this->assertNotEmpty($customerCart['prices']);
        $prices = $customerCart['prices'];
        $this->assertArrayHasKey('subtotal_excluding_tax', $prices);
        $this->assertNotEmpty($prices['subtotal_excluding_tax']);
        $this->assertArrayHasKey('subtotal_including_tax', $prices);
        $this->assertNotEmpty($prices['subtotal_including_tax']);
        $this->assertArrayHasKey('grand_total', $prices);
        $this->assertNotEmpty($prices['grand_total']);
        $this->assertArrayHasKey('is_credit_customer', $customerCart);
        $this->assertArrayHasKey('credit_customer', $customerCart);
        $this->assertNotEmpty($customerCart['credit_customer']);
        $creditCustomer = $customerCart['credit_customer'];
        $this->assertArrayHasKey('remaining_ar', $creditCustomer);
        $this->assertNotEmpty($creditCustomer['remaining_ar']);
        $this->assertArrayHasKey('overpayment', $creditCustomer);
        if (!empty($creditCustomer['overpayment'])) {
            $this->assertNotEmpty($creditCustomer['overpayment']);
        }
        $this->assertArrayHasKey('minimum_payment', $creditCustomer);
        if (!empty($creditCustomer['minimum_payment'])) {
            $this->assertNotEmpty($creditCustomer['minimum_payment']);
        }
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
