<?php
namespace Bat\CatalogGraphQl\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Bat\Customer\Helper\Data;
/**
 * @class FrequentProducts
 * Update customer attribute frequently ordered product
 */
class FrequentProducts implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $_orderCollectionFactory;

    /**
      * @var Data
      */
    private Data $customerHelperData;

    /**
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param Data $customerHelperData
     */
    public function __construct(
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        CollectionFactory $orderCollectionFactory,
        Data $customerHelperData
    ) {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->customerHelperData = $customerHelperData;
    }

    /**
     * Update frequent product attribute for customer
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customerId = $observer->getEvent()->getOrder()->getCustomerId();
        $orderStatus = $observer->getEvent()->getOrder()->getStatus();

        try {
            $products = [];
            $orderCollection = $this->getOrderCollectionByCustomerId($customerId);
            if ($orderCollection->count()) {
                foreach ($orderCollection as $customerOrder) {
                    foreach ($customerOrder->getAllItems() as $item) {
                        $productId = $item->getProductId();
                        if (array_key_exists($productId, $products)) {
                            $products[$productId] += 1;
                        } else {
                            $products[$productId] = 1;
                        }
                    }
                }
                arsort($products);
                $productId = array_key_first($products);
                $customer = $this->customerRepository->getById($customerId);
                $customer->setCustomAttribute('bat_frequently_ordered', $productId);
                $this->customerRepository->save($customer);
            }
        } catch (\Exception $e) {
            $msg = $customerId."-".$e->getMessage();
            $this->logger->error($msg);
        }
    }

    /**
     * Return order collection based on customer ID
     *
     * @param Int $customerId
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrderCollectionByCustomerId($customerId)
    {
        $collection = $this->_orderCollectionFactory->create($customerId)
            ->addFieldToSelect('*');
        return $collection;
    }
}
