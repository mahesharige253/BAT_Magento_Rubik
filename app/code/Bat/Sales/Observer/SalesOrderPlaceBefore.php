<?php
namespace Bat\Sales\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Bat\JokerOrder\Helper\Customer\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyData;

/**
 * @class AddOrderConsent
 * Add order consent to sales order
 */
class SalesOrderPlaceBefore implements ObserverInterface
{

    /**
     * @var CustomerRepositoryInterface
     */
     protected $customerRepository;

    /**
     * @var Data
     */
    protected $jokerOrderHelper;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var OrderFrequencyData
     */
    protected $orderFrequencyData;

    /**
     * SalesOrderPlaceBefore construct
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $jokerOrderHelper
     * @param TimezoneInterface $timezoneInterface
     * @param OrderFrequencyData $orderFrequencyData
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $jokerOrderHelper,
        TimezoneInterface $timezoneInterface,
        OrderFrequencyData $orderFrequencyData
    ) {
        $this->customerRepository = $customerRepository;
        $this->jokerOrderHelper = $jokerOrderHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->orderFrequencyData = $orderFrequencyData;
    }

    /**
     * Set order consent status
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        $order = $observer->getOrder();
        $customerId = $observer->getOrder()->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $orderFrequencyStatus = $this->orderFrequencyData->getOrderFrequencyStatus($customer);

        if ($orderFrequencyStatus == 3) {
            if ($this->jokerOrderHelper->getEcall($customer, $currentDate)) {
                $order->setData('order_type', __('E-Call'));
            } elseif ($this->jokerOrderHelper->getNpi($customer, $currentDate)) {
                $order->setData('order_type', __('NPI'));
            }

        } else {
            $order->setData('order_type', __('Sales Order'));
        }
        return $this;
    }
}
