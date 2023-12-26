<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Eav\Model\Config;
use Magento\Company\Api\CompanyManagementInterface;

/**
 * Transform single customer data from object to in array format
 */
class ExtractCustomerData extends \Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData
{
    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Eav\Model\Config $eavConfig
     */
    protected $_eavConfig;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyRepository;

    /**
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     * @param Config $eavConfig
     * @param CompanyManagementInterface $companyRepository
     */
    public function __construct(
        ServiceOutputProcessor $serviceOutputProcessor,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager,
        Config $eavConfig,
        CompanyManagementInterface $companyRepository
    ) {
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->serializer = $serializer;
        $this->_storeManager = $storeManager;
        $this->_eavConfig = $eavConfig;
        $this->companyRepository = $companyRepository;
    }

    /**
     * Curate default shipping and default billing keys
     *
     * @param array $arrayAddress
     * @return array
     */
    private function curateAddressData(array $arrayAddress): array
    {
        foreach ($arrayAddress as $key => $address) {
            if (!isset($address['default_shipping'])) {
                $arrayAddress[$key]['default_shipping'] = false;
            }
            if (!isset($address['default_billing'])) {
                $arrayAddress[$key]['default_billing'] = false;
            }
        }
        return $arrayAddress;
    }

    /**
     * Transform single customer data from object to in array format
     *
     * @param CustomerInterface $customer
     * @return array
     * @throws LocalizedException
     */
    public function execute(CustomerInterface $customer): array
    {
        $customerData = $this->serviceOutputProcessor->process(
            $customer,
            CustomerRepositoryInterface::class,
            'get'
        );
        $customerData['addresses'] = $this->curateAddressData($customerData['addresses']);
        if (isset($customerData['extension_attributes'])) {
            $customerData = array_merge($customerData, $customerData['extension_attributes']);
        }
        $customAttributes = [];
        $businessLicense = '';
        $tobaccoSellerLicense = '';
        if (isset($customerData['custom_attributes'])) {
            foreach ($customerData['custom_attributes'] as $attribute) {
                $isArray = false;
                if (is_array($attribute['value'])) {
                    $isArray = true;
                    foreach ($attribute['value'] as $attributeValue) {
                        if (is_array($attributeValue)) {
                            $customAttributes[$attribute['attribute_code']] = $this->serializer->serialize(
                                $attribute['value']
                            );
                            continue;
                        }
                        $customAttributes[$attribute['attribute_code']] = implode(',', $attribute['value']);
                        continue;
                    }
                }
                if ($isArray) {
                    continue;
                }
            }
        }
        $customerData = array_merge($customerData, $customAttributes);
        $customerMediaPath = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA).'customer';

        foreach ($customerData['custom_attributes'] as $key => $customAttr) {
            if ($customAttr['attribute_code'] == 'mobilenumber') {
                $customerData['mobilenumber'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'outlet_id') {
                $customerData['outlet_id'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'bat_business_license') {
                $businessLicense = base64_encode($customerMediaPath.$customAttr['value']);
                $customerData['custom_attributes'][$key]['value'] = $businessLicense;
                $customerData['business_license_file'] = $businessLicense;
            }
            if ($customAttr['attribute_code'] == 'bat_tobacco_seller_license') {
                $tobaccoSellerLicense = base64_encode($customerMediaPath.$customAttr['value']);
                $customerData['custom_attributes'][$key]['value'] = $tobaccoSellerLicense;
                $customerData['tobacco_license_file'] = $tobaccoSellerLicense;
            }
            if ($customAttr['attribute_code'] == 'bat_business_license_number') {
                $customerData['business_license_number'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'bat_tobacco_seller_license_number') {
                $customerData['tobacco_license_number'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'virtual_account') {
                $customerData['virtual_bank']['account_number'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'outlet_id') {
                $customerData['virtual_bank']['outlet_id'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'owner_gender') {
                $customerData['owner_gender'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'sigungu_code') {
                $customerData['sigungu_code'] = $customAttr['value'];
            }
            if ($customAttr['attribute_code'] == 'virtual_bank') {
                $bankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
                $bankName = $this->getAttributeLabelByValue('virtual_bank', $bankCode);
                $customerData['virtual_bank']['bank_details']['bank_name'] = $bankName;
                $customerData['virtual_bank']['bank_details']['bank_code'] = $bankCode;
                $customerData['virtual_bank']['account_holder_name'] = $customer->getFirstName();
            }
            if ($customAttr['attribute_code'] == 'secondary_email') {
                $customerData['secondary_email'] = $customAttr['value'];
            }
        }
        $company = $this->companyRepository->getByCustomerId($customer->getId());
        $companyname = '';
        if ($company) {
            $companyname = $company->getCompanyName();
        }
        $customerData['outlet_name'] = $companyname;
        //Fields are deprecated and should not be exposed on storefront.
        $customerData['group_id'] = null;
        $customerData['id'] = null;

        $customerData['model'] = $customer;

        //'dob' is deprecated, 'date_of_birth' is used instead.
        if (!empty($customerData['dob'])) {
            $customerData['date_of_birth'] = $customerData['dob'];
        }
        return $customerData;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabelByValue($attributeCode, $value)
    {
        try {
            $entityType = $this->_eavConfig->getEntityType('customer');
            $attribute  = $this->_eavConfig->getAttribute('customer', $attributeCode);
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
