<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\VirtualBank\Helper\Data;
use Magento\Company\Api\CompanyManagementInterface;

class VirtualBankAccount implements ResolverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Data
     */
    protected $virtualBankData;

     /**
      * @var CompanyManagementInterface
      */
    private $companyRepository;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $virtualBankData
     * @param CompanyManagementInterface $companyRepository
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $virtualBankData,
        CompanyManagementInterface $companyRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->virtualBankData = $virtualBankData;
        $this->companyRepository = $companyRepository;
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
        $customerId = '';
        if(isset($value['model'])) {
            $customerId = $value['model']->getCustomerId();
        }
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }
        $customer = $this->customerRepository->getById($customerId);
        $company = $this->companyRepository->getByCustomerId($customerId);
        $companyName = $company->getCompanyName();
        $virtualAccountNumber =  $customer->getCustomAttribute('virtual_account')->getValue();
        
        $virtualBank =  $customer->getCustomAttribute('virtual_bank')->getValue();
        $bankName = $this->virtualBankData->getVirtualBankName($virtualBank);
        $order = $value['model'];
        $payment = $order->getPayment()->getAdditionalInformation();
        
        if (isset($payment['vba_account_number']) && isset($payment['bankname'])) {
            return ['bank_name' => $payment['bankname'],
                    'account_number' => $payment['vba_account_number'],
                    'account_holder_name' => $companyName];
        } else {
            return ['bank_name' => $bankName,
                    'account_number' => $virtualAccountNumber,
                    'account_holder_name' => $companyName];
        }
    }
}
