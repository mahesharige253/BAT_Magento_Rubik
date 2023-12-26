<?php
declare(strict_types=1);

namespace Bat\VirtualBank\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\VirtualBank\Helper\Data;

/**
 * Sets payment additional information.
 */
class VbaOrderPaymentSaveObserver implements ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Data
     */
    protected $virtualBankData;

    /**
     * VbaOrderPaymentSaveObserver construct
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $virtualBankData
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Data $virtualBankData
    ) {
        $this->customerRepository = $customerRepository;
        $this->virtualBankData = $virtualBankData;
    }

    /**
     * Sets current instructions for bank transfer account
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $customerId = $payment->getOrder()->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $virtualAccountNumber =  $customer->getCustomAttribute('virtual_account')->getValue();
        $virtualBank =  $customer->getCustomAttribute('virtual_bank')->getValue();
        $bankName = $this->virtualBankData->getVirtualBankName($virtualBank);
        $payment->setAdditionalInformation('bankname', $bankName);
        $payment->setAdditionalInformation('vba_account_number', $virtualAccountNumber);
    }
}
