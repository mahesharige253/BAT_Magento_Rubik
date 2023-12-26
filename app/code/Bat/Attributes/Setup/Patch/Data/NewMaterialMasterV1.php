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
 * @class NewMaterialMasterV1
 * Create Material Master Attributes
 */
class NewMaterialMasterV1 implements DataPatchInterface, PatchRevertableInterface
{
    private const OPTION_IN_FOR = 'optionInfor';
    private const CERTIFICATIONS = 'certifications';
    private const IMAGES = 'images';
    private const NOTICES = 'notices';
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
        $categorySetup->addAttribute(Product::ENTITY, self::OPTION_IN_FOR, [
            'type'                    => 'text',
            'label'                   => 'Enriched Material Option In For',
            'input'                   => 'text',
            'source'                  => '',
            'default'                 => '',
            'unique'                  => false,
            'global'                  => ScopedAttributeInterface::SCOPE_STORE,
            'required'                => false,
            'is_used_in_grid'         => false,
            'visible_on_front'        => true,
            'is_filterable_in_grid'   => false,
            'user_defined'            => true,
            'validate'                => false,
            'visible'                 => true,
            'used_in_product_listing' => false,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'used_for_sort_by'        => false,
            'backend' => '',
            'frontend' => '',
        ]);
        $categorySetup->addAttribute(Product::ENTITY, self::CERTIFICATIONS, [
            'type'                    => 'text',
            'label'                   => 'Enriched Material Certifications',
            'input'                   => 'text',
            'source'                  => '',
            'default'                 => '',
            'unique'                  => false,
            'global'                  => ScopedAttributeInterface::SCOPE_STORE,
            'required'                => false,
            'is_used_in_grid'         => false,
            'visible_on_front'        => true,
            'is_filterable_in_grid'   => false,
            'user_defined'            => true,
            'validate'                => false,
            'visible'                 => true,
            'used_in_product_listing' => false,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'used_for_sort_by'        => false,
            'backend' => '',
            'frontend' => '',
        ]);

        $categorySetup->addAttribute(Product::ENTITY, self::IMAGES, [
            'type'                    => 'text',
            'label'                   => 'Enriched Material Images',
            'input'                   => 'text',
            'source'                  => '',
            'default'                 => '',
            'unique'                  => false,
            'global'                  => ScopedAttributeInterface::SCOPE_STORE,
            'required'                => false,
            'is_used_in_grid'         => false,
            'visible_on_front'        => true,
            'is_filterable_in_grid'   => false,
            'user_defined'            => true,
            'validate'                => false,
            'visible'                 => true,
            'used_in_product_listing' => false,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'used_for_sort_by'        => false,
            'backend' => '',
            'frontend' => '',
        ]);

        $categorySetup->addAttribute(Product::ENTITY, self::NOTICES, [
            'type'                    => 'text',
            'label'                   => 'Enriched Material Notices',
            'input'                   => 'text',
            'source'                  => '',
            'default'                 => '',
            'unique'                  => false,
            'global'                  => ScopedAttributeInterface::SCOPE_STORE,
            'required'                => false,
            'is_used_in_grid'         => false,
            'visible_on_front'        => true,
            'is_filterable_in_grid'   => false,
            'user_defined'            => true,
            'validate'                => false,
            'visible'                 => true,
            'used_in_product_listing' => false,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'used_for_sort_by'        => false,
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
                self::OPTION_IN_FOR,
                25
            );
            $categorySetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                self::ATTRIBUTE_GROUP,
                self::CERTIFICATIONS,
                25
            );
            $categorySetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                self::ATTRIBUTE_GROUP,
                self::IMAGES,
                25
            );
            $categorySetup->addAttributeToGroup(
                Product::ENTITY,
                $attributeSetId,
                self::ATTRIBUTE_GROUP,
                self::NOTICES,
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
        $categorySetup->removeAttribute(Product::ENTITY, self::OPTION_IN_FOR);
        $categorySetup->removeAttribute(Product::ENTITY, self::CERTIFICATIONS);
        $categorySetup->removeAttribute(Product::ENTITY, self::IMAGES);
        $categorySetup->removeAttribute(Product::ENTITY, self::NOTICES);
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
