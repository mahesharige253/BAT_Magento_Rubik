<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
use Bat\Sales\Model\BatOrderStatus;

/**
 * @class BatCustomOrderStatusV3
 * Add ZLOB Order Status
 */
class BatCustomOrderStatusV3 implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var StatusResourceFactory
     */
    private $statusResourceFactory;

    /**
     * Add ZLOB order Status constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResourceFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $statusArray = [
            BatOrderStatus::ZLOB_IN_PROGRESS_STATUS =>
                [BatOrderStatus::ZLOB_IN_PROGRESS_STATUS_LABEL, BatOrderStatus::PENDING_STATE ,false],
            BatOrderStatus::ZLOB_COMPLETE_STATUS =>
                [BatOrderStatus::ZLOB_COMPLETE_STATUS_LABEL, BatOrderStatus::COMPLETE_STATE, false],
        ];

        foreach ($statusArray as $key => $value) {
            $statusResource = $this->statusResourceFactory->create();
            $status = $this->statusFactory->create();
            $status->setData([
                'status' => $key,
                'label' => $value[0]
            ]);
            try {
                $statusResource->save($status);
            } catch (AlreadyExistsException $exception) {
                return;
            }
            $status->assignState($value[1], $value[2], true);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
