<?php
namespace Bat\JokerOrder\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Eav\Model\Entity\Attribute\Frontend\Datetime;

class JokerOrderAttributes implements DataPatchInterface
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
            'joker_order_npi_start_date',
            [
            'type' => 'datetime',
            'label' => 'Joker Order NPI - Start Date',
            'input' => 'date',
            'frontend' => Datetime::class,
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 310,
            'system' => false,
            'backend' => '',
            'user_defined' => true
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'joker_order_npi_start_date');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            Customer::ENTITY,
            'joker_order_npi_end_date',
            [
            'type' => 'datetime',
            'label' => 'Joker Order NPI - End Date',
            'input' => 'date',
            'frontend' => Datetime::class,
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 311,
            'system' => false,
            'backend' => '',
            'user_defined' => true
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'joker_order_npi_end_date');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            Customer::ENTITY,
            'joker_order_ecall_start_date',
            [
            'type' => 'datetime',
            'label' => 'Joker Order Ecall - Start Date',
            'input' => 'date',
            'frontend' => Datetime::class,
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 308,
            'system' => false,
            'backend' => '',
            'user_defined' => true
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'joker_order_ecall_start_date');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            Customer::ENTITY,
            'joker_order_ecall_end_date',
            [
            'type' => 'datetime',
            'label' => 'Joker Order Ecall - End Date',
            'input' => 'date',
            'frontend' => Datetime::class,
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 309,
            'system' => false,
            'backend' => '',
            'user_defined' => true
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'joker_order_ecall_end_date');
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
