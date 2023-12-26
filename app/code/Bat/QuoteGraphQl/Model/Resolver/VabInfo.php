<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\QuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\HTTP\Client\Curl;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @inheritdoc
 */
class VabInfo implements ResolverInterface
{

    /**
     * @var GetCustomer
     */
    protected $getCustomer;

    /**
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @var CompanyManagementInterface
     */
    protected $companyManagement;

    /**
     * @var \Magento\Eav\Model\Config $eavConfig
     */
    protected $_eavConfig;

    /**
     * @inheritdoc
     */
    public function __construct(
        GetCustomer $getCustomer,
        CompanyRepositoryInterface $companyRepository,
        CompanyManagementInterface $companyManagement,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->getCustomer = $getCustomer;
        $this->companyRepository = $companyRepository;
        $this->companyManagement = $companyManagement;
        $this->_eavConfig = $eavConfig;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $customer = $this->getCustomer->execute($context);
        
        $bankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
        $bankName = $this->getAttributeLabelByValue('virtual_bank', 'customer', $bankCode);
        $accountNumber = $customer->getCustomAttribute('virtual_account')->getValue();
        $accountHolderName = $this->getInfo($customer->getId());
        $data = [];

        $data['account_number'] = $accountNumber;
        $data['account_holder_name'] = $accountHolderName;
        $data['bank_details']['bank_code'] = $bankCode;
        $data['bank_details']['bank_name'] = $bankName;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getInfo($id)
    {
        $companyName = '';
        try{
            $company = $this->companyManagement->getByCustomerId($id);
            if($company){
                $companyName = $company->getCompanyName();
            }
        } catch (NoSuchEntityException $e) {
            return $companyName;
        } catch (LocalizedException $e) {
            return $companyName;
        }
        return $companyName;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabelByValue($attributeCode, $entityType, $value)
    {
        try {
            $entityType = $this->_eavConfig->getEntityType($entityType);
            $attribute  = $this->_eavConfig->getAttribute($entityType, $attributeCode);
            $options    = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
    
        return null;
    }
}
