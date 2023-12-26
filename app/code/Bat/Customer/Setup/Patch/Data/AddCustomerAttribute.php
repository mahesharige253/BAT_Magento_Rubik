<?php


namespace Bat\Customer\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Bat\Customer\Model\Entity\Attribute\Source\ApprovalStatus;

class AddCustomerAttribute implements DataPatchInterface, PatchRevertableInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var CustomerSetup
     */
    private $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeSetFactory $attributeSetFactory
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory,
        IndexerRegistry $indexerRegistry
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(
            Customer::ENTITY,
            'mobilenumber',
            [
                'group' => 'General',
                'type' => 'static',
                'label' => 'Mobile Number',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'unique' => false,
                'user_defined' => true,
                'sort_order' => 100,
                'position' => 100,
                'used_in_grid' => true,
                'visible_in_grid' => true,
                'searchable_in_grid' => true,
                'filterable_in_grid' => true,
                'system' => 0
            ]
        );

        $mobilenumber = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'mobilenumber')->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [
                'adminhtml_customer'
            ],
            'is_used_for_customer_segment' => true,
            'is_system' => 0,
            'is_user_defined' => 1,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true
        ]);
        $mobilenumber->save();

        $customerSetup->addAttribute(
            Customer::ENTITY,
            'outlet_id',
            [
                'group' => 'General',
                'type' => 'static',
                'label' => 'Outlet Id',
                'input' => 'text',
                'unique' => true,
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'sort_order' => 100,
                'position' => 100,
                'used_in_grid' => true,
                'visible_in_grid' => true,
                'searchable_in_grid' => true,
                'filterable_in_grid' => true,
                'system' => 0
            ]
        );

        $outletId = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'outlet_id')->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [
                'adminhtml_customer'
            ],
            'is_used_for_customer_segment' => true,
            'is_system' => 0,
            'is_user_defined' => 1,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true
        ]);
        $outletId->save();

        $customerSetup->addAttribute(
            Customer::ENTITY,
            'approval_status',
            [
                'group' => 'General',
                'type' => 'int',
                'label' => 'Approval Status',
                'input' => 'select',
                'source' => ApprovalStatus::class,
                'default' => '0',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'sort_order' => 100,
                'position' => 100,
                'used_in_grid' => true,
                'visible_in_grid' => true,
                'searchable_in_grid' => true,
                'filterable_in_grid' => true,
                'system' => 0
            ]
        );

        $approvalStatus = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'approval_status')->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [
                'adminhtml_customer'
            ],
            'is_used_for_customer_segment' => true,
            'is_system' => 0,
            'is_user_defined' => 1,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true
        ]);
        $approvalStatus->save();

        $indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
        $indexer->invalidate();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(Customer::ENTITY, 'mobilenumber');
        $customerSetup->removeAttribute(Customer::ENTITY, 'outlet_id');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
