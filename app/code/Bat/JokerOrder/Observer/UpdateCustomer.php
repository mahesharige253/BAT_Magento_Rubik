<?php
namespace Bat\JokerOrder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\JokerOrder\Helper\Customer\Data;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyData;
use Bat\JokerOrder\Model\JokerOrderCancellation;

/**
 * @class UpdateCustomer jokerOrder attributes
 */
class UpdateCustomer implements ObserverInterface
{
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerRepositoryInterface
     */
     protected $customerRepository;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var Data
     */
    protected $jokerOrderHelper;

    /**
     * @var OrderFrequencyData
     */
    protected $orderFrequencyData;

     /**
     * @var JokerOrderCancellation
     */
    protected $jokerOrderCancellation;

    /**
     * @param CustomerFactory $customerFactory
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepository
     * @param TimezoneInterface $timezoneInterface
     * @param Data $jokerOrderHelper
     * @param OrderFrequencyData $orderFrequencyData
     * @param JokerOrderCancellation $jokerOrderCancellation
     */
    public function __construct(
        CustomerFactory $customerFactory,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        TimezoneInterface $timezoneInterface,
        Data $jokerOrderHelper,
        OrderFrequencyData $orderFrequencyData,
        JokerOrderCancellation $jokerOrderCancellation
    ) {
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->timezoneInterface = $timezoneInterface;
        $this->jokerOrderHelper = $jokerOrderHelper;
        $this->orderFrequencyData = $orderFrequencyData;
        $this->jokerOrderCancellation = $jokerOrderCancellation;
    }

    /**
     * Customer e-call|npi data reset update
     *
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $customerId = $observer->getEvent()->getOrder()->getCustomerId();
        $currentDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        try {
            $customer = $this->customerFactory->create()->load($customerId);
            $customerRepository = $this->customerRepository->getById($customerId);
            $customerDataModel = $customer->getDataModel();
            $orderFrequencyStatus = $this->orderFrequencyData->getOrderFrequencyStatus($customerRepository);
            $orderType = $observer->getEvent()->getOrder()->getOrderType();
            $orderIncrementId = $observer->getEvent()->getOrder()->getIncrementId();
            if ($orderFrequencyStatus == 3 && $orderType != 'Sales Order') {
                if ($this->jokerOrderHelper->getEcall($customerRepository, $currentDate)) {
                    $this->jokerOrderCancellation->saveEcallJokerOrder($customerRepository,$customerId, $orderIncrementId);
                    $customerDataModel->setCustomAttribute('joker_order_ecall_start_date', '');
                    $customerDataModel->setCustomAttribute('joker_order_ecall_end_date', '');
                } elseif ($this->jokerOrderHelper->getNpi($customerRepository, $currentDate)) {
                    $this->jokerOrderCancellation->saveNpiJokerOrder($customerRepository,$customerId, $orderIncrementId);
                    $customerDataModel->setCustomAttribute('joker_order_npi_start_date', '');
                    $customerDataModel->setCustomAttribute('joker_order_npi_end_date', '');
                }
                $customer->updateData($customerDataModel);
                $customer->save();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
