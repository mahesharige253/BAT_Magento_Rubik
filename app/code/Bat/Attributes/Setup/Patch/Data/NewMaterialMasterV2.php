<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

/**
 * @class NewMaterialMasterV2
 * Create Material Master Attributes
 */
class NewMaterialMasterV2 implements DataPatchInterface, PatchRevertableInterface
{
    private const BATCH_ID = 'batch_id';
    private const FLAVOR = 'flavor';
    private const ATTRIBUTE_GROUP = 'Material Master';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * CreateProductAttributes constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup      = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * Create Attribute
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Validator\ValidateException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId(Product::ENTITY);
        $categorySetup->addAttribute(Product::ENTITY, self::BATCH_ID, [
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'Batch Id',
            'input' => 'text',
            'class' => '',
            'source' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => false,
            'unique' => false,
            'apply_to' => '',
            'group' => 'Material Master',
        ]);
        $categorySetup->addAttribute(Product::ENTITY, self::FLAVOR, [
            'type'                    => 'varchar',
            'label'                   => 'Flavor',
            'input'                   => 'text',
            'source'                  => '',
            'default'                 => '',
            'unique'                  => false,
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
            'required'                => false,
            'is_used_in_grid'         => false,
            'visible_on_front'        => true,
            'is_filterable_in_grid'   => false,
            'user_defined'            => true,
            'validate'                => false,
            'visible'                 => true,
            'used_in_product_listing' => true,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'used_for_sort_by'        => false,
            'used_for_promo_rules'    => true,
            'backend' => '',
            'frontend' => '',
        ]);

        if (isset($attributeSetId)) {
            $categorySetup->addAttributeGroup(
                Product::ENTITY,
                $attributeSetId,
                self::ATTRIBUTE_GROUP,
                1
            );
            $categorySetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                self::ATTRIBUTE_GROUP,
                self::BATCH_ID,
                25
            );
            $categorySetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                self::ATTRIBUTE_GROUP,
                self::FLAVOR,
                25
            );
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Remove attribute if exists
     *
     * @return array|void
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $categorySetup->removeAttribute(Product::ENTITY, self::BATCH_ID);
        $categorySetup->removeAttribute(Product::ENTITY, self::FLAVOR);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Return dependencies
     *
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Return Aliases
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }
}
