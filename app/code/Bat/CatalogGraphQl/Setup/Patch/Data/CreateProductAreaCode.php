<?php
namespace Bat\CatalogGraphQl\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

class CreateProductAreaCode implements DataPatchInterface
{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * EavSetupFactory Class
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
      * Apply method to create attribute
      */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            'catalog_product',
            'bat_product_area_code',
            [
                'group' => 'Product Details',
                'type' => 'text',
                'sort_order' => 200,
                'backend' => '',
                'frontend' => '',
                'label' => 'Area code',
                'input' => 'text',
                'class' => '',
                'source' => Boolean::class,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'required' => false,
                'searchable' => false,
                'visible_in_advanced_search'=>false,
                'filterable' => true,
                'comparable' => false,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'used_for_sort_by'=>false,
                'user_defined' => true,
                'unique' => false,
                'apply_to' => ''
            ]
        );
    }

    /**
     * Get Dependencies
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Get Aliases
     */
    public function getAliases()
    {
        return [];
    }
}
