<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\Customer\Helper\Data;

class ParentOutletData implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $helperData
     */
    public function __construct(
        GetCustomer $getCustomer,
        CustomerRepositoryInterface $customerRepository,
        Data $helperData
    ) {
        $this->getCustomer = $getCustomer;
        $this->customerRepository = $customerRepository;
        $this->helperData = $helperData;
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
        $isBulkOrderShow = false;
        $customer = $this->getCustomer->execute($context);
        $customerDetatils = $this->customerRepository->getById($customer->getId());

        if (!empty($customerDetatils->getCustomAttribute('is_parent'))) {
            $parentId = $customerDetatils->getCustomAttribute('is_parent')->getValue();
            if (!empty($customerDetatils->getCustomAttribute('outlet_id'))) {
                $outletId = $customerDetatils->getCustomAttribute('outlet_id')->getValue();
                $childOutlets = $this->helperData->getChildOutlet($outletId);
                if(count($childOutlets) > 1) {
                    $isBulkOrderShow  = true;
                }
            }
        }
        return $isBulkOrderShow;
    }
}
