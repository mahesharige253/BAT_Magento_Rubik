<?php
namespace Bat\Customer\Setup\Patch\Data;

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

class AccountDisclosureAttributes implements DataPatchInterface, PatchRevertableInterface
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
            'bank_account_card',
            [
            'type' => 'text',
            'label' => 'Bank Account Card',
            'input' => 'file',
            'source' => '',
            'required' => false,
            'visible' => true,
            'position' => 220,
            'system' => false,
            'backend' => ''
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'bank_account_card');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'account_closing_date',
            [
                'label' => 'Account Closing Date',
                'input' => 'text',
                'required' => false,
                'sort_order' => 88,
                'position' => 88,
                'system' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'account_closing_date');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);
       
        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'returning_stock',
            [
                'type' => 'text',
                'label' => 'Return Stock',
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
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'returning_stock');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'disclosure_approval_status',
            [
                'type' => 'int',
                'label' => 'Disclosure Approval Status',
                'input' => 'select',
                'source' => DisclosureApprovalStatus::class,
                'required' => false,
                'visible' => false,
                'user_defined' => true,
                'sort_order' => 101,
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
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'disclosure_approval_status');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'disclosure_rejected_fields',
            [
                'type' => 'text',
                'label' => 'Disclosure Rejected Fields',
                'input' => 'multiselect',
                'source' => DisclosureRejectedFields::class,
                'required' => false,
                'visible' => false,
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
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'disclosure_rejected_fields');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'disclosure_rejected_reason',
            [
                'label' => 'Disclosure Rejection Statement',
                'input' => 'text',
                'required' => false,
                'sort_order' => 88,
                'position' => 88,
                'system' => false,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'is_searchable_in_grid' => false
            ]
        );

        $attributeSetId = $eavSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $eavSetup->getDefaultAttributeGroupId(Customer::ENTITY);
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'disclosure_rejected_reason');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'account_deactivate',
            [
                'type' => 'text',
                'label' => 'Account Deactivated',
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
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'account_deactivate');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'product_return',
            [
                'type' => 'text',
                'label' => 'Product Returned',
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
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'product_return');
        $attribute->setData('attribute_set_id', $attributeSetId);
        $attribute->setData('attribute_group_id', $attributeGroupId);
        $attribute->setData('used_in_forms', [
           'adminhtml_customer',
           'customer_account_edit',
           'customer_account_create'
        ]);
        $this->attributeResource->save($attribute);

        $eavSetup->addAttribute(
            \Magento\Customer\Model\Customer::ENTITY,
            'disclosure_consent_form_selected',
            [
                'type' => 'text',
                'label' => 'Disclosure Consent Form Selected',
                'input' => 'select',
                'required' => false,
                'default' => 0,
                'source' => Boolean::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => false,
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
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, 'disclosure_consent_form_selected');
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
        $customerSetup->removeAttribute(Customer::ENTITY, 'account_closing_date');
 
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
