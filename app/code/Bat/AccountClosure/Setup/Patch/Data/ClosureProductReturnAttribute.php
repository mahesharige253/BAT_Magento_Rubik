<?php
namespace Bat\AccountClosure\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Bat\Customer\Model\Entity\Attribute\Source\DisclosureApprovalStatus;
use Bat\Customer\Model\Entity\Attribute\Source\DisclosureRejectedFields;

class ClosureProductReturnAttribute implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
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
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param Attribute $attributeResource
     * @param ModuleDataSetupInterface $moduleDataSetup
     *
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        Attribute $attributeResource,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeResource = $attributeResource;
        $this->moduleDataSetup = $moduleDataSetup;
    }

     /**
      * Apply method to create attribute
      */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create();
        $entityTypeId = 1;
              
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'closure_request_admin',
            [
                'type' => 'text',
                'label' => 'Closure Request Admin',
                'input' => 'select',
                'required' => false,
                'source' => Boolean::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => false,
                'default' => 0,
                'user_defined' => true,
                'sort_order' => 100,
                'position' => 100,
                'used_in_grid' => false,
                'visible_in_grid' => false,
                'searchable_in_grid' => false,
                'filterable_in_grid' => false,
                'system' => 0
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'closure_request_admin');
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
    * @inheritdoc
    */
    public static function getDependencies()
    {
        return [];
    }
   
   /**
    * @inheritdoc
    */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(Customer::ENTITY, 'closure_request_admin');
 
        $this->moduleDataSetup->getConnection()->endSetup();
    }

   /**
    * @inheritdoc
    */
    public function getAliases()
    {
        return [];
    }
}
