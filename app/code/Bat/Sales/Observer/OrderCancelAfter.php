<?php
namespace Bat\Sales\Observer;

use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource\CollectionFactory as EdaOrdersCollectionFactory;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * @class OrderCancelAfter
 * Set Cancelled order status for frontend
 */
class OrderCancelAfter implements ObserverInterface
{
    /**
     * @var EdaOrdersCollectionFactory
     */
    private EdaOrdersCollectionFactory $edaOrdersCollectionFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param EdaOrdersCollectionFactory $edaOrdersCollectionFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        EdaOrdersCollectionFactory $edaOrdersCollectionFactory,
        EdaOrdersResource $edaOrdersResource,
        LoggerInterface $logger
    ) {
        $this->edaOrdersCollectionFactory = $edaOrdersCollectionFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->logger = $logger;
    }

    /**
     * Set canceled order status for frontend
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getOrder();
        $order->setTotalRefunded($order->getTotalPaid());
        try {
             $edaOrderCollection = $this->edaOrdersCollectionFactory->create()->addFieldToSelect('*')
                ->addFieldToFilter('order_id', ['eq'=>$order->getEntityId()]);
            foreach ($edaOrderCollection as $edaOrder) {
                $this->edaOrdersResource->delete($edaOrder);
            }
        } catch (\Throwable $e) {
            $this->logger->info('Order cancellation exception occured : '.$e->getMessage());
        }
        return $this;
    }
}
