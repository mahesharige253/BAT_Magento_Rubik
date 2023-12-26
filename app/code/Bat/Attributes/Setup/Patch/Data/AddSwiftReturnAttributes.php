<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Rma\Setup\RmaSetup;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\Rma\Setup\RmaSetupFactory;
use Magento\SalesSequence\Model\Config as SequenceConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddSwiftReturnAttributes implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var RmaSetupFactory
     */
    private $rmaSetupFactory;

    /**
     * @var ConfigInterface
     */
    private $productTypeConfig;

    /**
     * @var Builder
     */
    private $sequenceBuilder;

    /**
     * @var SequenceConfig
     */
    private $sequenceConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RmaSetupFactory $setupFactory
     * @param ConfigInterface $productTypeConfig
     * @param Builder $sequenceBuilder
     * @param SequenceConfig $sequenceConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RmaSetupFactory $setupFactory,
        ConfigInterface $productTypeConfig,
        Builder $sequenceBuilder,
        SequenceConfig $sequenceConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->rmaSetupFactory = $setupFactory;
        $this->productTypeConfig = $productTypeConfig;
        $this->sequenceBuilder = $sequenceBuilder;
        $this->sequenceConfig = $sequenceConfig;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function apply()
    {
        //Add Rma Attributes
        /** @var RmaSetup $installer */
        $installer = $this->rmaSetupFactory->create(['setup' => $this->moduleDataSetup]);

        /**
         * Prepare database before module installation
         */
        $installer->installEntities();

        $installer->addAttribute(
            'rma_item',
            'fresh_requested',
            [
                'type' => 'static',
                'label' => 'Fresh Requested',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 45,
                'position' => 45
            ]
        );

        $installer->addAttribute(
            'rma_item',
            'old_requested',
            [
                'type' => 'static',
                'label' => 'Old Requested',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 46,
                'position' => 46
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'damage_requested',
            [
                'type' => 'static',
                'label' => 'Damage Requested',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 47,
                'position' => 47
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
