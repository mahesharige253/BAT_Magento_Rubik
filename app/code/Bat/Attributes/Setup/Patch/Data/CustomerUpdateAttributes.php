<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @class CustomerUpdateAttributes
 * Create Customer attributes
 */
class CustomerUpdateAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private const BAT_BATCH_ID = 'bat_batch_id';
    private const BAT_CREATED_AT = 'bat_created_at';
    private const BAT_COUNTRY_CODE = 'bat_country_code';
    private const BAT_COMPANY_CODE = 'bat_company_code';
    private const SAP_OUTLET_CODE = 'sap_outlet_code';

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

        /*create batch id attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::BAT_BATCH_ID, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Batch Id',
            'required' => false,
            'default' => '',
            'sort_order' => 221,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 221
        ]);
        $batchId = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::BAT_BATCH_ID
        );
        $batchId->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $batchId->save();
        /*create batch id attribute */

        /*create at attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::BAT_CREATED_AT, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Created At',
            'required' => false,
            'default' => '',
            'sort_order' => 222,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 222
        ]);
        $createdAt = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::BAT_CREATED_AT
        );
        $createdAt->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $createdAt->save();
        /*create at attribute */

        /*create company name attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::BAT_COMPANY_CODE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Company Code',
            'required' => false,
            'default' => '',
            'sort_order' => 223,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 223
        ]);
        $companyCode = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::BAT_COMPANY_CODE
        );
        $companyCode->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $companyCode->save();
        /*create company name attribute */

        /*create country code attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::BAT_COUNTRY_CODE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Country Code',
            'required' => false,
            'default' => '',
            'sort_order' => 224,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 224
        ]);
        $countryCode = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::BAT_COUNTRY_CODE
        );
        $countryCode->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $countryCode->save();
        /*create country code attribute */

        /*create sap outlet code attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::SAP_OUTLET_CODE, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Sap Outlet Code',
            'required' => false,
            'default' => '',
            'sort_order' => 225,
            'visible' => true,
            'unique' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => false,
            'is_used_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'position' => 225
        ]);
        $sapOutletCode = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::SAP_OUTLET_CODE
        );
        $sapOutletCode->addData([
            'used_in_forms' => ['adminhtml_customer'],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $sapOutletCode->save();
        /*create sap outlet code attribute */
    }

    /**
     * Remove attribute if exists
     *
     * @return array|void
     */
    public function revert()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerSetup->removeAttribute(Customer::ENTITY, self::BAT_BATCH_ID);
        $customerSetup->removeAttribute(Customer::ENTITY, self::BAT_COMPANY_CODE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::BAT_COUNTRY_CODE);
        $customerSetup->removeAttribute(Customer::ENTITY, self::BAT_CREATED_AT);
        $customerSetup->removeAttribute(Customer::ENTITY, self::SAP_OUTLET_CODE);
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
