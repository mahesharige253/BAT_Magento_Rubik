<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Frontend\Datetime;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

/**
 * @class CustomerDeactivatedAt
 * Create Customer deactivated at attribute
 */
class CustomerDeactivatedAt implements DataPatchInterface, PatchRevertableInterface
{
    private const DEACTIVATED_AT = 'deactivated_at';

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

        $customerSetup->addAttribute(Customer::ENTITY, self::DEACTIVATED_AT, [
            'type' => 'static',
            'input' => 'date',
            'label' => 'Deactivated At',
            'required' => false,
            'default' => '0',
            'frontend' => Datetime::class,
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
        $batchId = $this->eavConfig->getAttribute(
            Customer::ENTITY,
            self::DEACTIVATED_AT
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
    }

    /**
     * Remove attribute if exists
     *
     * @return array|void
     */
    public function revert()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);
        $customerSetup->removeAttribute(Customer::ENTITY, self::DEACTIVATED_AT);
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
