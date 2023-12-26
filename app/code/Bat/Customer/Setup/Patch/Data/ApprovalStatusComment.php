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
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

class ApprovalStatusComment implements DataPatchInterface, PatchRevertableInterface
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
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory     $customerSetupFactory
     * @param AttributeSetFactory      $attributeSetFactory
     * @param IndexerRegistry          $indexerRegistry
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
            'approval_status_comment',
            [
                'group' => 'General',
                'type' => 'text',
                'label' => 'Approval Status Comment',
                'input' => 'textarea',
                'unique' => false,
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'sort_order' => 116,
                'position' => 116,
                'used_in_grid' => false,
                'visible_in_grid' => false,
                'searchable_in_grid' => false,
                'filterable_in_grid' => false,
                'system' => 0
            ]
        );
        $statusComment = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'approval_status_comment')->addData(
            [
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            'used_in_forms' => [
                'adminhtml_customer'
            ],
            'is_used_for_customer_segment' => false,
            'is_system' => 0,
            'is_user_defined' => 1,
            'is_used_in_grid' => false,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false
            ]
        );
        $statusComment->save();
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
        /**
        * @var CustomerSetup $customerSetup
        */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerSetup->removeAttribute(Customer::ENTITY, 'approval_status_comment');
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
