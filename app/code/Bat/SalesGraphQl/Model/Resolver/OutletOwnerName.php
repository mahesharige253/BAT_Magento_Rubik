<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Company\Api\CompanyManagementInterface;

class OutletOwnerName implements ResolverInterface
{
    /**
     * @var CompanyManagementInterface
     */
    protected $companyRepository;

    /**
     * Contructor
     *
     * @param CompanyManagementInterface $companyRepository
     */
    public function __construct(
        CompanyManagementInterface $companyRepository
    ) {
        $this->companyRepository = $companyRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $customerId = '';
        if(isset($value['model'])) {
            $customerId = $value['model']->getCustomerId();
        }
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $outletOwnerName = '';
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }

        $company = $this->companyRepository->getByCustomerId($customerId);
        if (isset($company) && !empty($company)) {
            $outletOwnerName = $company->getCompanyName();
        }
        return $outletOwnerName;
    }
}
