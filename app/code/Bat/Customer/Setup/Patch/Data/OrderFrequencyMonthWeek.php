<?php
namespace Bat\Customer\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\ResourceModel\Attribute;

class OrderFrequencyMonthWeek implements DataPatchInterface
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
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute
     */
    private $attributeResource;
        
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param Attribute $attributeResource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        Attribute $attributeResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeResource = $attributeResource;
    }

     /**
      * Apply method to create attribute
      */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute(
            Customer::ENTITY,
            'order_frequency_month',
            [
                'type' => 'varchar',
                'label' => 'Order Frequency Month',
                'input' => 'select',
                'source' => \Bat\Customer\Model\Entity\Attribute\Source\OrderMonthOptions::class,
                'required' => false,
                'visible' => true,
                'position' => 82,
                'sort_order' => 82,
                'default' => '',
                'system' => false,
                'backend' => '',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'order_frequency_month');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            Customer::ENTITY,
            'order_frequency_week',
            [
                'type' => 'varchar',
                'label' => 'Order Frequency Week',
                'input' => 'select',
                'source' => \Bat\Customer\Model\Entity\Attribute\Source\OrderWeekOptions::class,
                'required' => false,
                'visible' => true,
                'position' => 83,
                'sort_order' => 83,
                'default' => '',
                'system' => false,
                'backend' => '',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'order_frequency_week');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer'
        ]);
        $this->attributeResource->save($attribute);
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
