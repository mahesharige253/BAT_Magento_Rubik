<?php
namespace Bat\Sales\Observer;

use Bat\Sales\Helper\Data;
use Bat\Sales\Model\BatOrderStatus;
use Bat\Sales\Model\EdaOrderType;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Bat\CustomerBalance\Helper\Data as CustomerBalanceHelper;

/**
 * @class UpdateOrder
 * Save order placed details to eda pending orders table
 */
class UpdateOrder implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var CustomerBalanceHelper
     */
    private CustomerBalanceHelper $customerBalanceHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var BalanceFactory
     */
    private BalanceFactory $_balanceFactory;

    /**
     * @param LoggerInterface $logger
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param Data $dataHelper
     * @param CustomerBalanceHelper $customerBalanceHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param BalanceFactory $balanceFactory
     */
    public function __construct(
        LoggerInterface $logger,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        Data $dataHelper,
        CustomerBalanceHelper $customerBalanceHelper,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->logger = $logger;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->dataHelper = $dataHelper;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Save order placed details to eda pending orders table
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();
        try {
            $orderType =  $order->getEdaOrderType();
            if ($orderType == EdaOrderType::ZLOB) {
                $order->setTotalPaid($order->getTotalDue());
                $order->setTotalDue(0);
                $order->setBaseTotalDue(0);
            }
            $updateToEda = false;
            if ($orderType == EdaOrderType::ZLOB || $orderType == EdaOrderType::ZREONE ||
                $orderType == EdaOrderType::IRO) {
                $updateToEda = true;
            }
            if ($order->getGrandTotal() == $order->getTotalPaid() && $orderType == EdaOrderType::ZLOB) {
                $updateToEda = true;
                $order->setState(BatOrderStatus::PENDING_STATE);
                $order->setStatus(BatOrderStatus::ZLOB_IN_PROGRESS_STATUS);
                $order->save();
            }
            if ($order->getGrandTotal() == $order->getTotalPaid() && $orderType == EdaOrderType::ZOR) {
                $updateToEda = true;
            }
            if ($orderType == EdaOrderType::ZREONE) {
                $order->setState(BatOrderStatus::PROCESSING_STATE);
                $order->setStatus(BatOrderStatus::RETURN_REQUEST_CLOSED);
                $order->save();
            }
            if ($orderType == EdaOrderType::IRO) {
                $order->setState(BatOrderStatus::PROCESSING_STATE);
                $order->setStatus(BatOrderStatus::RETURN_IN_PROGRESS_STATUS);
                $order->save();
            }
            if ($updateToEda) {
                $edaPendingOrder = $this->edaOrdersFactory->create();
                $edaPendingOrder->setData(
                    [
                        'order_id' => $order->getId(),
                        'order_type' => $orderType,
                        'channel' => ($orderType == EdaOrderType::ZOR) ? 'SWIFTPLUS' : 'OMS',
                        'order_increment_id' => $order->getIncrementId()
                    ]
                );
                $this->edaOrdersResource->save($edaPendingOrder);
            }
            $order->save();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
