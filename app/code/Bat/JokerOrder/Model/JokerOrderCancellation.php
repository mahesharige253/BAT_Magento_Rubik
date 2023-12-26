<?php

namespace Bat\JokerOrder\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\CustomerSegment\Model\Customer;
use Psr\Log\LoggerInterface;
use Bat\JokerOrder\Model\JokerOrderDataFactory;

class JokerOrderCancellation
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var JokerOrderDataFactory
     */
    protected $jokerOrderDataFactory;

    /**
     * Construct method
     *
     * @param Customer $customer
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param JokerOrderDataFactory $jokerOrderDataFactory
     */
    public function __construct(
        Customer $customer,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        JokerOrderDataFactory $jokerOrderDataFactory
    ) {
        $this->customer = $customer;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->jokerOrderDataFactory = $jokerOrderDataFactory;
    }

    /**
     * save E call joker order

     * @param object $customer
     * @param string $orderIncrementId
     */
    public function saveEcallJokerOrder($customer, $customerId, $orderIncrementId)
    {
        try {
            if (
                !empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_ecall_end_date'))
            ) {
                $jokerOrderEcallStartDate = $customer->getCustomAttribute('joker_order_ecall_start_date')->getValue();
                $jokerOrderEcallEndDate = $customer->getCustomAttribute('joker_order_ecall_end_date')->getValue();
                $dataModel = $this->jokerOrderDataFactory->create();
                $dataModel->setData(
                    [
                        'customer_id' => $customerId,
                        'e_call_start_date' => $jokerOrderEcallStartDate,
                        'e_call_end_date' => $jokerOrderEcallEndDate,
                        'order_id' => $orderIncrementId,
                    ]
                );
            }
            $dataModel->save();
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
    * save Npi joker order

    * @param int $customerId
    * @param string $orderIncrementId
    */
    public function saveNpiJokerOrder($customer, $customerId, $orderIncrementId)
    {
        try {
            if (
                !empty($customer->getCustomAttribute('joker_order_npi_start_date'))
                && !empty($customer->getCustomAttribute('joker_order_npi_end_date'))
            ) {
                $jokerOrderNpiStartDate = $customer->getCustomAttribute('joker_order_npi_start_date')->getValue();
                $jokerOrderNpiEndDate = $customer->getCustomAttribute('joker_order_npi_end_date')->getValue();
                $dataModel = $this->jokerOrderDataFactory->create();
                $dataModel->setData(
                    [
                        'customer_id' => $customerId,
                        'npi_start_date' => $jokerOrderNpiStartDate,
                        'npi_end_date' => $jokerOrderNpiEndDate,
                        'order_id' => $orderIncrementId,
                    ]
                );
            }
            $dataModel->save();
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
    * return Joker Order Frequency

    * @param int $customerId
    * @param string $orderIncrementId
    */
    public function returnJokerOrder($customerId, $orderIncrementId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $collection = $this->jokerOrderDataFactory->create()->getCollection();
            $dataModel = $this->jokerOrderDataFactory->create();
            $collection->addFieldToFilter('order_id', $orderIncrementId);
            $records = $collection->getData();
            if ($records > 0) {
                $jokerOrderStartDate ='';
                $jokerOrderEndDate = '';
                $jokerOrderNpiStartDate = '';
                $jokerOrderNpiEndDate = '';
                if(isset($records[0]['e_call_start_date'])){
                    $jokerOrderStartDate = $records[0]['e_call_start_date'];
                }
                if(isset($records[0]['e_call_end_date'])){
                    $jokerOrderEndDate = $records[0]['e_call_end_date'];
                }
                if(isset($records[0]['npi_start_date'])){
                    $jokerOrderNpiStartDate = $records[0]['npi_start_date'];
                }
                if(isset($records[0]['npi_end_date'])){
                    $jokerOrderNpiEndDate = $records[0]['npi_end_date'];
                }
                if (
                    empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
                    && empty($customer->getCustomAttribute('joker_order_ecall_end_date'))
                ) {
                    $customer->setCustomAttribute('joker_order_ecall_start_date', $jokerOrderStartDate);
                    $customer->setCustomAttribute('joker_order_ecall_end_date', $jokerOrderEndDate);
                    $this->customerRepository->save($customer);
                }
                if (
                    empty($customer->getCustomAttribute('joker_order_npi_start_date'))
                    && empty($customer->getCustomAttribute('joker_order_npi_end_date'))
                ) {
                    $customer->setCustomAttribute('joker_order_npi_start_date', $jokerOrderNpiStartDate);
                    $customer->setCustomAttribute('joker_order_npi_end_date', $jokerOrderNpiEndDate);
                    $this->customerRepository->save($customer);
                }
            }
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__($e->getMessage()), $e);
        }
    }
}
