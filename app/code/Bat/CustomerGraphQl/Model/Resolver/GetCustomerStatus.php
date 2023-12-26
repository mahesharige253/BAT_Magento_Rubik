<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as AttributeCollection;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\Customer\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Eav\Model\Config;
use Bat\Integration\Helper\Data as IntegrationData;
use Magento\Customer\Model\Customer;
use Magento\Framework\Api\AttributeInterface;
use Magento\Company\Api\CompanyManagementInterface;

/**
 * Customer Group Code field resolver
 */
class GetCustomerStatus implements ResolverInterface
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AttributeCollection
     */
    private $attributeCollection;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var IntegrationData
     */
    private $integrationData;

    /**
     * @var Config
     */
    private $eavconfig;

     /**
      * @var CompanyManagementInterface
      */
    private $companyRepository;

    /**
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AttributeCollection $attributeCollection
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param ExtractCustomerData $extractCustomerData
     * @param AddressRepositoryInterface $addressRepository
     * @param IntegrationData $integrationData
     * @param Config $eavconfig
     * @param CompanyManagementInterface $companyRepository
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        AttributeCollection $attributeCollection,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        ExtractCustomerData $extractCustomerData,
        AddressRepositoryInterface $addressRepository,
        IntegrationData $integrationData,
        Config $eavconfig,
        CompanyManagementInterface $companyRepository
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->attributeCollection = $attributeCollection;
        $this->_scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->extractCustomerData = $extractCustomerData;
        $this->addressRepository = $addressRepository;
        $this->integrationData = $integrationData;
        $this->eavconfig = $eavconfig;
        $this->companyRepository = $companyRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['mobilenumber'])) {
            throw new GraphQlInputException(__('Mobile number must be specified'));
        }
        //        if (isset($args['mobilenumber'])
        // && !preg_match("/010 ([0-9]{3}|[0-9]{4}) [0-9]{4}$/", $args['mobilenumber'])) {
        //            throw new GraphQlInputException(__('Mobile number value is not valid'));
        //        }
        $mobileNumber = $args['mobilenumber'];
        $customers = $this->helper->getCustomer("mobilenumber", $mobileNumber);
        $data = [];
        if ($customers->getSize() > 0) {
            $i = 0;
            foreach ($customers as $customer) {
                $customerId = $customer->getId();
                $customerDetatils = $this->customerRepository->getById($customerId);
                $optionId = (!empty($customerDetatils->getCustomAttribute('approval_status')))?
                            $customerDetatils->getCustomAttribute('approval_status')->getValue():0;
                $attribute = $this->eavconfig->getAttribute('customer', 'approval_status');
                $sourceModel = $attribute->getSource();
                if ($sourceModel->getOptionText($optionId) != 'Closure Account Terminated') {
                    $data[$i]['customer'] = '';
                    $data[$i]['outlet_id'] = $customerDetatils->getCustomAttribute('outlet_id')->getValue();
                    $data[$i]['key_id'] = $this->integrationData->encryptData($data[$i]['outlet_id']);
                    $addressId = $customerDetatils->getDefaultShipping();
                    if ($addressId != '') {
                        $shippingAddress = $this->addressRepository->getById($addressId);
                        if ($shippingAddress) {
                            $data[$i]['address']['postcode'] = $shippingAddress->getPostcode();
                            $street = $shippingAddress->getStreet();
                            $data[$i]['address']['street']['street1'] = (isset($street[0])) ? $street[0] : '';
                            $data[$i]['address']['street']['street2'] = (isset($street[1])) ? $street[1] : '';
                        }
                    }
                    $company = $this->companyRepository->getByCustomerId($customerId);
                    if (isset($company)) {
                        $data[$i]['outlet_name'] = $company->getCompanyName();
                    }
                    $data[$i]['approval_status'] = $sourceModel->getOptionText($optionId);
                    if (!empty($customerDetatils->getCustomAttribute('outlet_pin')) &&
                    $customerDetatils->getCustomAttribute('outlet_pin')->getValue()
                        != '' && $data[$i]['approval_status'] != 'Under Review') {
                        $data[$i]['approval_status'] = 'Completed';
                    } else {
                        if (!in_array(
                            $data[$i]['approval_status'],
                            ['New', 'Rejected', 'Resubmitted', 'Under Review']
                        )) {
                            $data[$i]['approval_status'] = 'Approved';
                        }
                    }
                    $data[$i]['paper_forms'] = $customerDetatils->getCustomAttribute('bat_paper_forms')->getValue();
                    if (empty($customerDetatils->getCustomAttribute('approval_status'))
                    || in_array($customerDetatils->getCustomAttribute('approval_status')->getValue(), [0, 3, 5])) {
                        $data[$i]['heading'] = $this->getCustomerUnderReviewHeading();
                        $data[$i]['message'] = $this->getCustomerUnderReviewMessage();
                    } elseif ($customerDetatils->getCustomAttribute('approval_status')->getValue() == 1) {
                        $data[$i]['heading'] = $this->getCustomerApprovedHeading();
                        $data[$i]['message'] = $this->getCustomerApprovedMessage();
                    } elseif ($customerDetatils->getCustomAttribute('approval_status')->getValue() == 2) {
                        $data[$i]['heading'] = $this->getCustomerRejectedHeading();
                        $data[$i]['message'] = $this->getCustomerRejectedMessage();
                        
                        $data[$i]['rejected_fields'] = 'business_license_file,tobacco_license_file,street1,street2,postcode';
                        $data[$i]['customer'] = $this->extractCustomerData->execute($customerDetatils);
                    }
                    $data[$i]['call_center_number'] = $this->getCustomerCallCenterNumber();
                    $data[$i]['registered_consents'] = (!empty($customerDetatils->getCustomAttribute('consentform'))) ?
                    $customerDetatils->getCustomAttribute('consentform')->getValue() : '';
                    $i++;
                }
            }
        } else {
            $data[0]['heading'] = $this->getCustomerNotFoundHeading();
            $data[0]['message'] = $this->getCustomerNotFoundMessage();
        }
        return $data;
    }

    /**
     * GetCustomerNotFoundHeading
     */
    public function getCustomerNotFoundHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_notfound_heading");
        return $data;
    }

    /**
     * GetCustomerNotFoundMessage
     */
    public function getCustomerNotFoundMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_notfound_message");
        return $data;
    }

    /**
     * GetCustomerApprovedHeading
     */
    public function getCustomerApprovedHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_approved_heading");
        return $data;
    }

    /**
     * GetCustomerApprovedMessage
     */
    public function getCustomerApprovedMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_approved_message");
        return $data;
    }

    /**
     * GetCustomerRejectedHeading
     */
    public function getCustomerRejectedHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_rejected_heading");
        return $data;
    }

    /**
     * GetCustomerRejectedMessage
     */
    public function getCustomerRejectedMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_application_rejected_message");
        return $data;
    }

    /**
     * GetCustomerUnderReviewHeading
     */
    public function getCustomerUnderReviewHeading()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_under_review_heading");
        return $data;
    }

    /**
     * GetCustomerUnderReviewMessage
     */
    public function getCustomerUnderReviewMessage()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_under_review_message");
        return $data;
    }

    /**
     * GetCustomerCallCenterNumber
     */
    public function getCustomerCallCenterNumber()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_callcenter_number");
        return $data;
    }

}
