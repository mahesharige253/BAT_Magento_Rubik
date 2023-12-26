<?php

namespace Bat\Attributes\Setup\Patch\Data;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
use Bat\Sales\Model\BatOrderStatus;

/**
 * @class BatCustomOrderStatus
 * Add customer order status
 */
class BatCustomOrderStatus implements DataPatchInterface
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
     * AddDeliveredOrderStateAndStatus constructor.
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
            BatOrderStatus::RETURN_IN_PROGRESS_STATUS =>
                [BatOrderStatus::RETURN_IN_PROGRESS_STATUS_LABEL, BatOrderStatus::PROCESSING_STATE ,false],
            BatOrderStatus::SHIPPED_STATUS =>
                [BatOrderStatus::SHIPPED_LABEL, BatOrderStatus::PROCESSING_STATE, true],
            BatOrderStatus::DELIVERY_FAILED_STATUS =>
                [BatOrderStatus::DELIVERY_FAILED_LABEL, BatOrderStatus::CLOSED_STATE, false],
            BatOrderStatus::PREPARING_TO_SHIP_STATUS =>
                [BatOrderStatus::PREPARING_TO_SHIP_LABEL, BatOrderStatus::PENDING_STATE, false],
            BatOrderStatus::COMPLETED_STATUS =>
                [BatOrderStatus::COMPLETED_LABEL, BatOrderStatus::COMPLETE_STATE,true],
            BatOrderStatus::FAILURE_STATUS =>
                [BatOrderStatus::FAILURE_LABEL, BatOrderStatus::PENDING_STATE,false],
            BatOrderStatus::FAILURE_STATUS =>
                [BatOrderStatus::FAILURE_LABEL, BatOrderStatus::PENDING_STATE,false],
            BatOrderStatus::UNPAID_STATUS =>
                [BatOrderStatus::UNPAID_LABEL, BatOrderStatus::PENDING_STATE,true],
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
