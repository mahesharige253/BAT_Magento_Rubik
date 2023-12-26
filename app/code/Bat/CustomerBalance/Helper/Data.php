<?php
namespace Bat\CustomerBalance\Helper;

use Bat\CustomerBalance\Model\OrderBalanceModelFactory;
use Bat\CustomerBalance\Model\ResourceModel\OrderBalanceResource;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

/**
 * @class Data
 * Helper class for CustomerBalance Update
 */
class Data extends AbstractHelper
{
    /**
     * @var BalanceFactory
     */
    private BalanceFactory $_balanceFactory;

    /**
     * @var OrderBalanceModelFactory
     */
    private OrderBalanceModelFactory $orderBalanceModelFactory;

    /**
     * @var OrderBalanceResource
     */
    private OrderBalanceResource $orderBalanceResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var OrderCollectionFactory
     */
    private OrderCollectionFactory $orderCollectionFactory;

    /**
     * @param BalanceFactory $balanceFactory
     * @param OrderBalanceModelFactory $orderBalanceModelFactory
     * @param OrderBalanceResource $orderBalanceResource
     * @param LoggerInterface $logger
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        BalanceFactory $balanceFactory,
        OrderBalanceModelFactory $orderBalanceModelFactory,
        OrderBalanceResource $orderBalanceResource,
        LoggerInterface $logger,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->orderBalanceModelFactory = $orderBalanceModelFactory;
        $this->orderBalanceResource = $orderBalanceResource;
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Update used credit for the order
     *
     * @param OrderInterface $order
     * @param CustomerInterface  $customer
     * @param float $overPayment
     * @param boolean $isCreditCustomer
     */
    public function updateStoreCreditBalanceUtilized($order, $customer, $overPayment, $isCreditCustomer)
    {
        $balance = $this->_balanceFactory->create()
            ->setCustomerId($customer->getId())
            ->setWebsiteId($customer->getWebsiteId());
        $balance = $balance->loadbyCustomer();
        $this->logStoreCreditBalanceChange('=============================================');
        $this->logStoreCreditBalanceChange('Customer Id : '.$customer->getId());
        $this->logStoreCreditBalanceChange('Before Change Order Id : '.$order->getIncrementId());
        $this->logStoreCreditBalanceValues($balance);
        $balance->setAmountDelta(-$overPayment)->setHistoryAction(History::ACTION_USED)->setOrder($order);
        $balance->save();
        $this->logStoreCreditBalanceChange('After Change Order Id : '.$order->getIncrementId());
        $this->logStoreCreditBalanceValues($balance);
    }

    /**
     * Log store credit balance change
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logStoreCreditBalanceChange($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/StoreCredit.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Log store credit balance values
     *
     * @param Balance
     */
    public function logStoreCreditBalanceValues($balance)
    {
        $creditInfo = [
            'credit_limit' => $balance->getCreditLimit(),
            'available_credit_limit' => $balance->getAmount(),
            'credit_exposure' => $balance->getCreditExposure(),
            'overdue_amount' => $balance->getOverdueAmount(),
            'overdue_flag' => $balance->getOverdueFlag()
        ];
        $this->logStoreCreditBalanceChange(json_encode($creditInfo));
    }

    /**
     * Return customer store credit balance amount
     *
     * @param CustomerInterface $customer
     * @return float
     * @throws LocalizedException
     */
    public function getCustomerStoreCreditBalance($customer)
    {
        $balance = $this->_balanceFactory->create()
            ->setCustomerId($customer->getId())
            ->setWebsiteId($customer->getWebsiteId());
        return $balance->loadbyCustomer()->getAmount();
    }

    /**
     * Update order balance to custom order balance table
     *
     * @param int $customerId
     * @param float $balance
     * @param string $comment
     * @param null $action
     * @param null $source
     */
    public function batOrderBalanceUpdate($customerId, $balance, $comment, $action = null, $source = null)
    {
        try {
            $exisingBalance = 0;
            $orderCustomBalance = $this->getBatOrderBalance($customerId);
            if ($orderCustomBalance) {
                $exisingBalance = $orderCustomBalance->getAvailableBalance();
            }
            $newCustomBalance = $this->orderBalanceModelFactory->create();
            if ($action == 'Updated' || $action == "Refunded") {
                $balanceChanged = $balance - $exisingBalance;
                $newCustomBalance->setData(
                    [
                        'customer_id' =>$customerId,
                        'available_balance'=>$balance,
                        'comment' => $comment,
                        'action' => $action,
                        'balance_changed' => $balanceChanged
                    ]
                );
            } else {
                $exisingBalance = $exisingBalance - $balance;
                $exisingBalance = ($exisingBalance < 0) ? 0 :$exisingBalance;
                $newCustomBalance->setData(
                    [
                        'customer_id' =>$customerId,
                        'available_balance'=>$balance,
                        'comment' => $comment,
                        'action' => 'Used',
                        'balance_changed' => $exisingBalance
                    ]
                );
            }
            $this->orderBalanceResource->save($newCustomBalance);
        } catch (\Exception $e) {
            $this->logger->info('Customer Balance update exception : '.$e->getMessage());
            $this->logger->info('Customer Balance update for Customer: '.$customerId. ', Balance: '.$balance);
        }
    }

    /**
     * Return custom balance object for create/update
     *
     * @param int $customerId
     */
    public function getBatOrderBalance($customerId)
    {
        $orderBalance = $this->orderBalanceModelFactory->create()->getCollection();
        $orderBalance->addFieldToFilter('customer_id', ['eq' => $customerId]);
        if ($orderBalance->getSize()) {
            return $orderBalance->getLastItem();
        }
        return '';
    }

    /**
     * Return custom order balance
     *
     * @param int $customerId
     * @return int
     */
    public function getAvailableOrderBalance($customerId)
    {
        $balance = 0;
        $orderBalance = $this->orderBalanceModelFactory->create()->getCollection();
        $orderBalance->addFieldToFilter('customer_id', ['eq' => $customerId]);
        if ($orderBalance->getSize()) {
            $balance = $orderBalance->getLastItem()->getAvailableBalance();
        }
        return $balance;
    }

    /**
     * Return used credit based on order collection
     *
     * @param bool $orderRelease
     * @param int $customerId
     * @param null $websiteId
     * @return float|int|mixed|null
     */
    public function getUsedCreditFromOrders($orderRelease, $customerId, $websiteId = null)
    {
        $usedCredit = 0;
        if ($websiteId == null) {
            $websiteId = 1;
        }
        try {
            $this->logStoreCreditBalanceChange('==============================================');
            $this->logStoreCreditBalanceChange('Customer Id : '.$customerId);
            $this->logStoreCreditBalanceChange('Type : '.$orderRelease);
            $orderCollectionCredit = $this->orderCollectionFactory->create($customerId)->addFieldToSelect('*');
            if ($orderRelease) {
                $orderCollectionCredit->addFieldToFilter(
                    'status',
                    [['eq' => 'preparing_to_ship'],['eq' => 'failure']]
                );
            } else {
                $orderCollectionCredit->addFieldToFilter(
                    'status',
                    [['eq' => 'pending'],['eq' => 'preparing_to_ship'],['eq' => 'failure']]
                );
            }
            $orderCollectionCredit->addFieldToFilter('sap_order_status', [['null' => true],['eq' => '002']])
                ->addFieldToFilter('eda_order_type', ['eq' => 'ZOR']);
            if ($orderCollectionCredit->getSize()) {
                $this->logStoreCreditBalanceChange('Orders Available In Collection');
                /** @var OrderInterface $orderDetails */
                foreach ($orderCollectionCredit as $orderDetails) {
                    $this->logStoreCreditBalanceChange(
                        'Orders Included for Calculation : '.$orderDetails->getIncrementId()
                    );
                    $status = $orderDetails->getStatus();
                    if ($status == 'preparing_to_ship' || $status == 'failure') {
                        $this->logStoreCreditBalanceChange('Total Paid used');
                        $usedCredit = $usedCredit + $orderDetails->getTotalPaid();
                    } else {
                        $this->logStoreCreditBalanceChange('Grand Total used');
                        $usedCredit = $usedCredit + $orderDetails->getGrandTotal();
                    }
                }
            }
            $this->logStoreCreditBalanceChange('Used Credit From calculation : '.$usedCredit);
            $storeCredit = $this->getStoreCreditBalance($customerId, $websiteId);
            $this->logStoreCreditBalanceChange('Store Credit from customer balance: '.$storeCredit);
            if ($storeCredit < 0) {
                $usedCredit = 0;
                $this->logStoreCreditBalanceChange('Used Credit set to Zero');
            } else {
                $usedCredit =  $storeCredit - $usedCredit;
                $this->logStoreCreditBalanceChange(
                    'Store credit not zero and available balance computed : '.$usedCredit
                );
                $usedCredit = ($usedCredit < 0) ? 0: $usedCredit;
                $this->logStoreCreditBalanceChange('Used Credit negative check : '. $usedCredit);
            }
            $this->logStoreCreditBalanceChange('Final balance : '.$usedCredit);
        } catch (\Exception $e) {
            $this->logStoreCreditBalanceChange('Exception occured : '. $e->getMessage());
            $this->logger->info('Available balance exception : '.$e->getMessage());
            $usedCredit = 0;
        }
        return $usedCredit;
    }


    /**
     * Return unpaid orders total
     *
     * @param int $customerId
     * @return float|int|mixed
     */
    public function getUnconfirmedOrdersTotal($customerId)
    {
        $unconfirmedTotal = 0;
        $orderCollectionCredit = $this->orderCollectionFactory->create($customerId)->addFieldToSelect('*');
        $orderCollectionCredit->addFieldToFilter('status', [['eq' => 'preparing_to_ship'],['eq' => 'failure']])
            ->addFieldToFilter('sap_order_status', [['null' => true],['eq' => '002']])
            ->addFieldToFilter('eda_order_type', ['eq' => 'ZOR']);
        if ($orderCollectionCredit->getSize()) {
            /** @var OrderInterface $orderDetails */
            foreach ($orderCollectionCredit as $orderDetails) {
                $unconfirmedTotal = $unconfirmedTotal + $orderDetails->getTotalPaid();
            }
        }
        return $unconfirmedTotal;
    }

    /**
     * Zlob orders
     *
     * @param int $customerId
     * @return boolean
     */
    public function getZlobOrders($customerId)
    {
        $orderCollectionCredit = $this->orderCollectionFactory->create($customerId);
        $orderCollectionCredit->addFieldToFilter('status', ['eq' => 'cancel_in_progress'])
            ->addFieldToFilter('eda_order_type', ['eq' => 'ZLOB']);
        if ($orderCollectionCredit->getSize()) {
           return true;
        }
        return false;
    }

    /**
     * Return unconfirmed orders total
     *
     * @param int $customerId
     * @return float|int|mixed|null
     */
    public function getUnpaidOrdersTotal($customerId)
    {
        $unpaidTotal = 0;
        $orderCollectionCredit = $this->orderCollectionFactory->create($customerId)->addFieldToSelect('*');
        $orderCollectionCredit->addFieldToFilter('status', ['eq' => 'pending'])
            ->addFieldToFilter('sap_order_status', [['null' => true],['eq' => '002']])
            ->addFieldToFilter('eda_order_type', ['eq' => 'ZOR']);
        if ($orderCollectionCredit->getSize()) {
            /** @var OrderInterface $orderDetails */
            foreach ($orderCollectionCredit as $orderDetails) {
                $unpaidTotal = $unpaidTotal + $orderDetails->getGrandTotal();
            }
        }
        return $unpaidTotal;
    }

    /**
     * Return customer store credit balance amount
     *
     * @param $customerId
     * @param $websiteId
     * @return float
     * @throws LocalizedException
     */
    public function getStoreCreditBalance($customerId, $websiteId)
    {
        $balance = $this->_balanceFactory->create()
            ->setCustomerId($customerId)
            ->setWebsiteId($websiteId);
        return $balance->loadbyCustomer()->getAmount();
    }
}
