<?php
namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\Customer\Helper\Data;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Eav\Model\Config;

class GetChildOutletData implements ResolverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CompanyManagementInterface
     */
    private $companyRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @var Config
     */
    private $eavconfig;

    /**
     *  @param Data $helper
     *  @param CompanyManagementInterface $companyRepository
     *  @param CustomerRepositoryInterface $customerRepositoryInterface
     *  @param Config $eavconfig
     */
    public function __construct(
        Data $helper,
        CompanyManagementInterface $companyRepository,
        CustomerRepositoryInterface $customerRepositoryInterface,
        Config $eavconfig
    ) {
        $this->helper = $helper;
        $this->companyRepository = $companyRepository;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->eavconfig = $eavconfig;
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
      
        if (empty($args['parentOutletId'])) {
            throw new GraphQlInputException(__('Parent Outlet Id value should be specified'));
        }
        
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $customerId = $context->getUserId();

        $parentOutletId = $args['parentOutletId'];
        
        $customers = $this->helper->getCustomer('parent_outlet_id', $parentOutletId);
        $data = [];
        if(count($customers) > 0) {
            $i = 0;
            foreach($customers as $customer) {
                $customerData = $this->_customerRepositoryInterface->getById($customer->getId());
                $company = $this->companyRepository->getByCustomerId($customer->getId());
                $companyName = $company->getCompanyName();
                $outletId = $customerData->getCustomAttribute('outlet_id')->getValue();
                $approvalStatus = $customerData->getCustomAttribute('approval_status')->getValue();
                $attribute = $this->eavconfig->getAttribute('customer', 'approval_status');
                $sourceModel = $attribute->getSource();
                
                //echo 'OutletId:'.$outletId;
                $data[$i]['outlet_id'] = $outletId;
                $data[$i]['outlet_name'] = $companyName;
                $data[$i]['status'] = $approvalStatus;
                $data[$i]['status_label'] = $sourceModel->getOptionText($approvalStatus);
                $i++;
            }
        }
        return $data;

    }
}
