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

class CreateLicenseNumberAttr implements DataPatchInterface
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
            'bat_business_license_number',
            [
            'type' => 'text',
            'label' => 'Business License Number',
            'input' => 'text',
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 210,
            'system' => false,
            'backend' => ''
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'bat_business_license_number');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            Customer::ENTITY,
            'bat_tobacco_seller_license_number',
            [
            'type' => 'text',
            'label' => 'Tobacco Seller License Number',
            'input' => 'text',
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 210,
            'system' => false,
            'backend' => ''
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'bat_tobacco_seller_license_number');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
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
