<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Bat\Attributes\Model\Source\ProductTags;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;

/**
 * @class ProductTagAttribute
 * Create Product Tag Attribute
 */
class ProductTagAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private const SET_PRODUCT_TAG = 'product_tag';
    private const ATTRIBUTE_GROUP = 'Product Details';

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
     * @return ProductTagAttribute|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Validator\ValidateException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CategorySetup $categorySetup */
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId(Product::ENTITY);
        $categorySetup->addAttribute(Product::ENTITY, self::SET_PRODUCT_TAG, [
            'type'                    => 'varchar',
            'label'                   => 'Product Tag',
            'input'                   => 'multiselect',
            'source'                  => ProductTags::class,
            'default'                 => '',
            'unique'                  => false,
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
            'required'                => false,
            'is_used_in_grid'         => false,
            'visible_on_front'        => true,
            'is_filterable_in_grid'   => false,
            'user_defined'            => true,
            'validate'                => true,
            'visible'                 => true,
            'used_in_product_listing' => true,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'used_for_sort_by'        => false,
            'backend'                 => ArrayBackend::class,
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
                self::SET_PRODUCT_TAG,
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
        $categorySetup->removeAttribute(Product::ENTITY, self::SET_PRODUCT_TAG);
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
