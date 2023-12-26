<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Bat\Attributes\Model\Source\PriceTagOptions;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

/**
 * @class PriceTagV1
 * Create PriceTag Attribute
 */
class PriceTagV1 implements DataPatchInterface, PatchRevertableInterface
{
    private const PRICETAG_TYPE = 'pricetag_type';
    private const ATTRIBUTE_GROUP = 'General';

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
        $categorySetup->addAttribute(Product::ENTITY, self::PRICETAG_TYPE, [
            'type' => 'int',
            'label' => 'Price Tag Type',
            'input' => 'select',
            'source' => PriceTagOptions::class,
            'sort_order' => 250,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => '0',
            'visible_on_front' => false,
            'used_for_promo_rules' => true,
            'used_in_product_listing' => true,
            'is_used_in_grid' => true,
            'is_filterable_in_grid' => true,
            'unique' => false
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
                self::PRICETAG_TYPE,
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
        $categorySetup->removeAttribute(Product::ENTITY, self::PRICETAG_TYPE);
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
