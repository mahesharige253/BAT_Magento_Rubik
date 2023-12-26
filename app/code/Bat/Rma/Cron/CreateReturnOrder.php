<?php

namespace Bat\Rma\Cron;

use Bat\Rma\Model\ResourceModel\ZreResource;
use Bat\Sales\Helper\Data;
use Bat\Rma\Helper\Data as RmaHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Bat\Rma\Model\NewRma\ReturnOrderModel;
use Psr\Log\LoggerInterface;

/**
 * @class CreateReturnOrder
 * Cron to create orders in EDA
 */
class CreateReturnOrder
{
    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ReturnOrderModel
     */
    private ReturnOrderModel $returnOrderModel;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var RmaHelper
     */
    private RmaHelper $rmaHelper;

    /**
     * @var ZreResource
     */
    private ZreResource $zreResource;

    /**
     * @var RmaRepositoryInterface
     */
    private RmaRepositoryInterface $rmaRepository;

    /**
     * @param Data $dataHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param ReturnOrderModel $returnOrderModel
     * @param LoggerInterface $logger
     * @param RmaHelper $rmaHelper
     * @param ZreResource $zreResource
     * @param RmaRepositoryInterface $rmaRepository
     */
    public function __construct(
        Data $dataHelper,
        OrderRepositoryInterface $orderRepository,
        ReturnOrderModel $returnOrderModel,
        LoggerInterface $logger,
        RmaHelper $rmaHelper,
        ZreResource $zreResource,
        RmaRepositoryInterface $rmaRepository
    ) {
        $this->dataHelper = $dataHelper;
        $this->orderRepository = $orderRepository;
        $this->returnOrderModel = $returnOrderModel;
        $this->logger = $logger;
        $this->rmaHelper = $rmaHelper;
        $this->zreResource = $zreResource;
        $this->rmaRepository = $rmaRepository;
    }

    /**
     * Create Return orders
     */
    public function execute()
    {
        $cronEnabled = $this->dataHelper->getSystemConfigValue(
            'bat_integrations/bat_order/eda_return_order_create_cron'
        );
        $this->returnOrderModel->getReturnOrderLogEnabledStatus();
        $this->returnOrderModel->createReturnOrderLog('========================================');
        if (!$cronEnabled) {
            $this->returnOrderModel->createReturnOrderLog('Return Order Create Cron Disabled');
            return;
        }
        try {
            $returnOrderCollection = $this->rmaHelper->getZrePendingOrders();
            if ($returnOrderCollection->count()) {
                foreach ($returnOrderCollection as $returnOrder) {
                    try {
                        $returnOrder->setOrderInProgress(1);
                        $this->zreResource->save($returnOrder);
                        $this->returnOrderModel->createReturnOrderLog('Order Id: '.$returnOrder['order_id']);
                        $rma = json_decode($returnOrder['rma_data'], true);
                        $order = $this->orderRepository->get($returnOrder['order_id']);
                        $ordersCreated = $this->returnOrderModel->processReturnOrders(
                            $rma,
                            $order,
                            $order->getReturnSwiftCode()
                        );
                        if ($ordersCreated['success']) {
                            $returnOrder->setOrderCreated(1);
                            $returnOrder->setOrderInProgress(0);
                            $this->zreResource->save($returnOrder);
                        } else {
                            $returnOrder->setOrderCreated(0);
                            $returnOrder->setOrderInProgress(0);
                            $returnOrder->setFailureAttempts($returnOrder->getFailureAttempts() + 1);
                            $this->zreResource->save($returnOrder);
                        }
                    } catch (\Exception $e) {
                        $returnOrder->setOrderCreated(0);
                        $returnOrder->setOrderInProgress(0);
                        $returnOrder->setFailureAttempts($returnOrder->getFailureAttempts() + 1);
                        $this->zreResource->save($returnOrder);
                        $this->returnOrderModel->createReturnOrderLog('Exception occurred: '.$e->getMessage());
                    }
                }
            } else {
                $this->returnOrderModel->createReturnOrderLog('No orders to create');
            }
        } catch (\Exception $e) {
            $this->logger->info('Return order cron exception'.$e->getMessage());
            $this->returnOrderModel->createReturnOrderLog('Exception occurred: '.$e->getMessage());
        }
    }
}
