<?php
declare(strict_types=1);

namespace Bat\CustomerAddressGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;

class ShippingAdditionalDetails implements ResolverInterface
{
    /**
     * @var CompanyManagementInterface
     */
    private $companyRepository;

    /**
     * @var CompanyManagementInterface
     */
    private $customerRepositoryInterface;

    /**
     * @param CompanyManagementInterface $companyRepository
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        CompanyRepositoryInterface $companyRepository,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->companyRepository = $companyRepository;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $mobilenumber = '';
        $companyName = '';
        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();
        $customerId = $context->getUserId();
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }
        $customer =$this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        if (isset($customerCustomAttributes['mobilenumber'])) {
            $mobileNumberAttribute = $customerCustomAttributes['mobilenumber'];
            if ($mobileNumberAttribute->getAttributecode() == "mobilenumber") {
                if ($mobileNumberAttribute->getValue()) {
                    $mobilenumber = $mobileNumberAttribute->getValue();
                }
            }
        }
        $extensionAttributes = $customer->getExtensionAttributes();
        $company = $extensionAttributes->getCompanyAttributes();
        $companyId = $company->getCompanyId();
        if ($companyId != 0) {
            $companyName = $this->companyRepository->get($companyId)->getCompanyName();
        }
                
        $shippingAdditionalDetails =  ["outlet_name" => $customer->getFirstname(),
                                        "outlet_owner_name" => $companyName,
                                        "phone_number" =>  $mobilenumber];
        return $shippingAdditionalDetails;
    }
}
