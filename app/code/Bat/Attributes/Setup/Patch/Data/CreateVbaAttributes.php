<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Bat\Attributes\Model\Source\Banks;

/**
 * @class CreateVbaAttributes
 * Create VBA customer attributes
 */
class CreateVbaAttributes implements DataPatchInterface, PatchRevertableInterface
{
    private const VIRTUAL_BANK_ACCOUNT = 'virtual_account';
    private const VIRTUAL_BANK_NAME = 'virtual_bank';

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

        /*create virtual bank account attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::VIRTUAL_BANK_ACCOUNT, [
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Bank Account Number',
            'required' => true,
            'default' => '',
            'sort_order' => 31,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => true,
            'is_used_in_grid' => true,
            'is_filterable_in_grid' => true,
            'is_searchable_in_grid' => true,
            'position' => 92
        ]);
        $virtualBankAttribute = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::VIRTUAL_BANK_ACCOUNT
        );
        $virtualBankAttribute->addData([
            'used_in_forms' => ['customer_account_create', 'customer_account_edit', 'adminhtml_customer'],
            'is_used_for_customer_segment' => true,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $virtualBankAttribute->save();
        /*create virtual bank account attribute */

        /*create virtual bank name attribute */
        $customerSetup->addAttribute(Customer::ENTITY, self::VIRTUAL_BANK_NAME, [
            'type' => 'varchar',
            'input' => 'select',
            'label' => 'Bank Name',
            'source' => Banks::class,
            'required' => true,
            'default' => '',
            'sort_order' => 32,
            'visible' => true,
            'user_defined' => true,
            'system' => false,
            'is_visible_in_grid' => true,
            'is_used_in_grid' => true,
            'is_filterable_in_grid' => true,
            'is_searchable_in_grid' => true,
            'position' => 91
        ]);
        $virtualAccountAttribute = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::VIRTUAL_BANK_NAME
        );
        $virtualAccountAttribute->addData([
            'used_in_forms' => ['customer_account_create', 'customer_account_edit', 'adminhtml_customer'],
            'is_used_for_customer_segment' => true,
            'is_system' => 0,
            'is_user_defined' => 1,
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroup
        ]);
        $virtualAccountAttribute->save();
        /*create virtual bank name attribute */
    }

    /**
     * Remove attribute if exists
     *
     * @return array|void
     */
    public function revert()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerSetup->removeAttribute(Customer::ENTITY, self::VIRTUAL_BANK_ACCOUNT);
        $customerSetup->removeAttribute(Customer::ENTITY, self::VIRTUAL_BANK_NAME);
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
