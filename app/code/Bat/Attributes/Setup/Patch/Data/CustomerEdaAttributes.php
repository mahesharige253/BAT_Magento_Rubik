<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Bat\Attributes\Model\Source\EdaCustomerBusinessType;
use Bat\Attributes\Model\Source\EdaCustomerBusinessItem;
use Bat\Attributes\Model\Source\EdaCustomerDeliveryPlant;
use Bat\Attributes\Model\Source\EdaCustomerPaymentTerm;
use Bat\Attributes\Model\Source\EdaCustomerSalesOffice;
use Bat\Attributes\Model\Source\EdaCustomerAccountGroup;
use Bat\Attributes\Model\Source\EdaCustomerTaxCode;
use Bat\Attributes\Model\Source\EdaCustomerOwnerGender;
use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @class CustomerEdaAttributes
 * Create Customer attributes for EDA
 */
class CustomerEdaAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private const PAYMENT_TYPE = 'payment_type';
    private const CUSTOMER_GROUP_FIVE = 'cust_group_five';
    private const PAYMENT_TERM = 'payment_term';
    private const DELIVERY_PLANT = 'delivery_plant';
    private const SALES_OFFICE = 'sales_office';
    private const GST_NUMBER = 'gst_number';
    private const BUSINESS_LICENCE_TYPE = 'business_licence_type';
    private const BUSINESS_LICENCE_ITEM = 'business_licence_item';
    private const OWNER_BIRTH_YEAR = 'owner_birth_year';
    private const OWNER_GENDER = 'owner_gender';
    private const ADDITIONAL_GST_NUMBER = 'additional_gst_number';
    private const SAP_VENDOR_CODE = 'sap_vendor_code';
    private const SALES_ORGANIZATION = 'sales_organization';
    private const DIVISION = 'division';
    private const PRICING_PROCEDURE = 'pricing_procedure';
    private const CUSTOMER_GROUP = 'customer_group';
    private const DELIVERY_PRIORITY = 'delivery_priority';
    private const SHIPPING_CONDITION = 'shipping_condition';
    private const SALES_GROUP = 'sales_group';
    private const ACCOUNT_GROUP = 'account_group';
    private const INCOTERM_ONE = 'incoterm_one';
    private const INCOTERM_TWO = 'incoterm_two';
    private const PREFERRED_COMMUNICATION = 'preferred_communication';
    private const DISTRIBUTION_CHANNEL = 'distribution_channel';
    private const MARKET_CONSENT_GIVEN = 'market_consent_given';
    private const TAX_CODE = 'tax_code';
    private const LANGUAGE_CODE = 'language_code';

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * CreateCustomerAttributes constructor.
     * @param ModuleDataSetupInterface $setup
     * @param Config $eavConfig
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        Config $eavConfig,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->setup = $setup;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerSetup->getDefaultAttributeSetId($customerEntity->getEntityTypeId());
        $attributeGroup = $customerSetup->getDefaultAttributeGroupId(
            $customerEntity->getEntityTypeId(),
            $attributeSetId
        );

        /*create payment_type attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::PAYMENT_TYPE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Payment Type',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 232
        ]);
        $paymentType = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::PAYMENT_TYPE
        );
        $paymentType->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $paymentType->save();
        /*create payment_type  attribute */

        /*create payment_term attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::PAYMENT_TERM, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Payment Term',
            'required' => false,
            'default' => '',
            'source' => EdaCustomerPaymentTerm::class,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 233
        ]);
        $paymentTerm = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::PAYMENT_TERM
        );
        $paymentTerm->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $paymentTerm->save();
        /*create payment_term  attribute */

        /*create cust_group_five attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::CUSTOMER_GROUP_FIVE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'CustGroup5',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 233
        ]);
        $customerGroupFive = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::CUSTOMER_GROUP_FIVE
        );
        $customerGroupFive->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $customerGroupFive->save();
        /*create cust_group_five  attribute */

        /*create delivery_plant attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::DELIVERY_PLANT, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Delivery Plant',
            'required' => false,
            'default' => '',
            'source' => EdaCustomerDeliveryPlant::class,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 234
        ]);
        $deliveryPlant = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::DELIVERY_PLANT
        );
        $deliveryPlant->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $deliveryPlant->save();
        /*create delivery_plant  attribute */

        /*create sales_office attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::SALES_OFFICE, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Sales Office',
            'required' => false,
            'source' => EdaCustomerSalesOffice::class,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 235
        ]);
        $salesOffice = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::SALES_OFFICE
        );
        $salesOffice->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $salesOffice->save();
        /*create sales_office  attribute */

        /*create gst_number attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::GST_NUMBER, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Gst Number',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 236
        ]);
        $gstNumber = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::GST_NUMBER
        );
        $gstNumber->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $gstNumber->save();
        /*create gst_number  attribute */

        /*create additional_gst_number attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::ADDITIONAL_GST_NUMBER, [
            'type' => 'int',
            'input' => 'text',
            'label' => 'Additional GST Number',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 237
        ]);
        $additionalGstNumber = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::ADDITIONAL_GST_NUMBER
        );
        $additionalGstNumber->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $additionalGstNumber->save();
        /*create additional_gst_number  attribute */

        /*create business_licence_type attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::BUSINESS_LICENCE_TYPE, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Business Licence Type',
            'source' => EdaCustomerBusinessType::class,
            'default' => '',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 238
        ]);
        $businessLicenceType = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::BUSINESS_LICENCE_TYPE
        );
        $businessLicenceType->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $businessLicenceType->save();
        /*create business_licence_type  attribute */

        /*create business_licence_item attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::BUSINESS_LICENCE_ITEM, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Business Licence Item',
            'source' => EdaCustomerBusinessItem::class,
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 239
        ]);
        $businessLicenceItem = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::BUSINESS_LICENCE_ITEM
        );
        $businessLicenceItem->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $businessLicenceItem->save();
        /*create business_licence_item  attribute */

        /*create sap_vendor_code attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::SAP_VENDOR_CODE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Sap Vendor Code',
            'required' => false,
            'default' => '',
            'visible' => true,
            'unique' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 240
        ]);
        $sapVendorCode = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::SAP_VENDOR_CODE
        );
        $sapVendorCode->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $sapVendorCode->save();
        /*create sap_vendor_code  attribute */

        /*create sales_organization attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::SALES_ORGANIZATION, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Sales Organization',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 242
        ]);
        $salesOrganization = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::SALES_ORGANIZATION
        );
        $salesOrganization->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $salesOrganization->save();
        /*create sales_organization  attribute */

        /*create division attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::DIVISION, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Division',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 243
        ]);
        $division = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::DIVISION
        );
        $division->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $division->save();
        /*create division  attribute */

        /*create pricing_procedure attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::PRICING_PROCEDURE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Pricing Procedure',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 244
        ]);
        $pricingProcedure = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::PRICING_PROCEDURE
        );
        $pricingProcedure->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $pricingProcedure->save();
        /*create pricing_procedure  attribute */

        /*create customer_group attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::CUSTOMER_GROUP, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Customer Group',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 245
        ]);
        $customerGroup = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::CUSTOMER_GROUP
        );
        $customerGroup->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $customerGroup->save();
        /*create customer_group  attribute */

        /*create delivery_priority attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::DELIVERY_PRIORITY, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Delivery Priority',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 246
        ]);
        $deliveryPriority = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::DELIVERY_PRIORITY
        );
        $deliveryPriority->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $deliveryPriority->save();
        /*create delivery_priority  attribute */

        /*create shipping_condition attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::SHIPPING_CONDITION, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Shipping Condition',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 247
        ]);
        $shippingCondition = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::SHIPPING_CONDITION
        );
        $shippingCondition->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $shippingCondition->save();
        /*create shipping_condition  attribute */

        /*create sales_group attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::SALES_GROUP, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Sales Group',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 248
        ]);
        $salesGroup = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::SALES_GROUP
        );
        $salesGroup->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $salesGroup->save();
        /*create sales_group  attribute */

        /*create account_group attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::ACCOUNT_GROUP, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Account Group',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 248
        ]);
        $accountGroup = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::ACCOUNT_GROUP
        );
        $accountGroup->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $accountGroup->save();
        /*create account_group  attribute */

        /*create incoterm_one attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::INCOTERM_ONE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Incoterm1',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 249
        ]);
        $incotermOne = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::INCOTERM_ONE
        );
        $incotermOne->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $incotermOne->save();
        /*create incoterm_one  attribute */

        /*create incoterm_two attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::INCOTERM_TWO, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Incoterm2',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 250
        ]);
        $incotermTwo = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::INCOTERM_TWO
        );
        $incotermTwo->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $incotermTwo->save();
        /*create incoterm_two  attribute */

        /*create preferred_communication attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::PREFERRED_COMMUNICATION, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Preferred Communication',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 251
        ]);
        $preferredCommunication = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::PREFERRED_COMMUNICATION
        );
        $preferredCommunication->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $preferredCommunication->save();
        /*create preferred_communication  attribute */

        /*create distribution_channel attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::DISTRIBUTION_CHANNEL, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Distribution Channel',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 252
        ]);
        $distributionChannel = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::DISTRIBUTION_CHANNEL
        );
        $distributionChannel->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $distributionChannel->save();
        /*create distribution_channel  attribute */

        /*create market_consent_given attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::MARKET_CONSENT_GIVEN, [
            'type' => 'int',
            'input' => 'boolean',
            'label' => 'Market Consent Given',
            'required' => false,
            'default' => '0',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 253
        ]);
        $marketConsentGiven = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::MARKET_CONSENT_GIVEN
        );
        $marketConsentGiven->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $marketConsentGiven->save();
        /*create market_consent_given  attribute */

        /*create owner_birth_year attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::OWNER_BIRTH_YEAR, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Owner Birth Year',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 255
        ]);
        $ownerBirthYear = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::OWNER_BIRTH_YEAR
        );
        $ownerBirthYear->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $ownerBirthYear->save();
        /*create owner_birth_year  attribute */

        /*create owner_gender attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::OWNER_GENDER, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Owner Gender',
            'source' => EdaCustomerOwnerGender::class,
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 256
        ]);
        $ownerGender = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::OWNER_GENDER
        );
        $ownerGender->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $ownerGender->save();
        /*create owner_gender  attribute */

        /*create tax_code attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::TAX_CODE, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Tax Code',
            'source' => EdaCustomerTaxCode::class,
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 257
        ]);
        $taxCode = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::TAX_CODE
        );
        $taxCode->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $taxCode->save();
        /*create tax_code  attribute */

        /*create language_code attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::LANGUAGE_CODE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Language Code',
            'required' => false,
            'default' => '',
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 259
        ]);
        $languageCode = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::LANGUAGE_CODE
        );
        $languageCode->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $languageCode->save();
        /*create language_code  attribute */
    }

    /**
     * Remove attribute if exists
     *
     * @return array|void
     */
    public function revert()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerSetup->removeAttribute(Customer::ENTITY, self::PAYMENT_TYPE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::PAYMENT_TERM);
        $customerSetup->removeAttribute(Customer::ENTITY, self::CUSTOMER_GROUP_FIVE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::DELIVERY_PLANT);
        $customerSetup->removeAttribute(Customer::ENTITY, self::SALES_OFFICE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::GST_NUMBER);
        $customerSetup->removeAttribute(Customer::ENTITY, self::ADDITIONAL_GST_NUMBER);
        $customerSetup->removeAttribute(Customer::ENTITY, self::BUSINESS_LICENCE_TYPE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::BUSINESS_LICENCE_ITEM);
        $customerSetup->removeAttribute(Customer::ENTITY, self::SAP_VENDOR_CODE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::SALES_ORGANIZATION);
        $customerSetup->removeAttribute(Customer::ENTITY, self::DIVISION);
        $customerSetup->removeAttribute(Customer::ENTITY, self::PRICING_PROCEDURE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::CUSTOMER_GROUP);
        $customerSetup->removeAttribute(Customer::ENTITY, self::DELIVERY_PRIORITY);
        $customerSetup->removeAttribute(Customer::ENTITY, self::SHIPPING_CONDITION);
        $customerSetup->removeAttribute(Customer::ENTITY, self::SALES_GROUP);
        $customerSetup->removeAttribute(Customer::ENTITY, self::ACCOUNT_GROUP);
        $customerSetup->removeAttribute(Customer::ENTITY, self::INCOTERM_ONE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::INCOTERM_TWO);
        $customerSetup->removeAttribute(Customer::ENTITY, self::PREFERRED_COMMUNICATION);
        $customerSetup->removeAttribute(Customer::ENTITY, self::DISTRIBUTION_CHANNEL);
        $customerSetup->removeAttribute(Customer::ENTITY, self::OWNER_BIRTH_YEAR);
        $customerSetup->removeAttribute(Customer::ENTITY, self::OWNER_GENDER);
        $customerSetup->removeAttribute(Customer::ENTITY, self::MARKET_CONSENT_GIVEN);
        $customerSetup->removeAttribute(Customer::ENTITY, self::TAX_CODE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::LANGUAGE_CODE);
    }

    /**
     * Return dependencies
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Return Aliases
     *
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
