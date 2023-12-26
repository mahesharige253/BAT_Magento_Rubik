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
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\Customer\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Bat\Integration\Helper\Data as IntegrationHelperData;

/**
 * Customer Group Code field resolver
 */
class GetRejectedCustomerData implements ResolverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

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
     * @var IntegrationHelperData
     */
    private $integrationHelperData;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param ExtractCustomerData $extractCustomerData
     * @param IntegrationHelperData $integrationHelperData
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        ExtractCustomerData $extractCustomerData,
        IntegrationHelperData $integrationHelperData
    ) {
        $this->customerRepository = $customerRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->extractCustomerData = $extractCustomerData;
        $this->integrationHelperData = $integrationHelperData;
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
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('Please enter Id value'));
        }
        $outletId = $this->integrationHelperData->decryptData($args['id']);
        $decryptFields = explode(",", $outletId);
        $outletId = $decryptFields[0];

        $customer = $this->helper->getCustomer("outlet_id", $outletId);

        $data = [];
        if ($customer->getSize() > 0) {
            $customerVal = $customer->getData();
            $customerId = $customerVal[0]['entity_id'];
            $customerDetatils = $this->customerRepository->getById($customerId);
            $data['customer'] = '';
            $data['outlet_id'] = $customerDetatils->getCustomAttribute('outlet_id')->getValue();
            $data['mobilenumber'] = $customerDetatils->getCustomAttribute('mobilenumber')->getValue();
            if ($customerDetatils->getCustomAttribute('approval_status')->getValue() == 2) {
                $data['heading'] = $this->getCustomerRejectedHeading();
                $data['message'] = $this->getCustomerRejectedMessage();
                $data['rejected_fields'] = (!empty($customerDetatils->getCustomAttribute('rejected_fields'))) ?
                    $customerDetatils->getCustomAttribute('rejected_fields')->getValue() : '';
                $data['customer'] = $this->extractCustomerData->execute($customerDetatils);
            }
            $data['call_center_number'] = $this->getCustomerCallCenterNumber();

        } else {
            $data['heading'] = $this->getCustomerNotFoundHeading();
            $data['message'] = $this->getCustomerNotFoundMessage();
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
     * GetCustomerCallCenterNumber
     */
    public function getCustomerCallCenterNumber()
    {
        $data = $this->_scopeConfig->getValue("customer_approval_status/general/customer_callcenter_number");
        return $data;
    }
}
