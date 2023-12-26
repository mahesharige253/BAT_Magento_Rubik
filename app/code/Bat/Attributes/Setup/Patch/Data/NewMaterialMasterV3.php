<?php
declare (strict_types = 1);

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class CreateCustomAttr for Create Custom Product Attribute using Data Patch.
 */
class NewMaterialMasterV3 implements DataPatchInterface
{

    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Class EavSetupFactory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory          $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute('catalog_product', 'tar', [
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'Tar',
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
            'visible_on_front' => true,
            'used_in_product_listing' => false,
            'unique' => false,
            'apply_to' => '',
            'group' => 'Material Master',
        ]);

        $eavSetup->addAttribute('catalog_product', 'nicotine', [
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'Nicotine',
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
            'visible_on_front' => true,
            'used_in_product_listing' => false,
            'unique' => false,
            'apply_to' => '',
            'group' => 'Material Master',
        ]);

        $eavSetup->addAttribute('catalog_product', 'product_category', [
            'type' => 'text',
            'backend' => '',
            'frontend' => '',
            'label' => 'Product Category',
            'input' => 'textarea',
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
            'visible_on_front' => true,
            'used_in_product_listing' => false,
            'unique' => false,
            'apply_to' => '',
            'group' => 'Material Master',
        ]);

        $eavSetup->addAttribute('catalog_product', 'bat_size', [
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'Size',
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
            'visible_on_front' => true,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => '',
            'group' => 'Material Master',
        ]);

        $eavSetup->addAttribute('catalog_product', 'fs1', [
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'F&S1',
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
            'visible_on_front' => true,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => '',
            'group' => 'Material Master',
        ]);

        $eavSetup->addAttribute('catalog_product', 'fs2', [
            'type' => 'varchar',
            'backend' => '',
            'frontend' => '',
            'label' => 'F&S2',
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
            'visible_on_front' => true,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => '',
            'group' => 'Material Master',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
