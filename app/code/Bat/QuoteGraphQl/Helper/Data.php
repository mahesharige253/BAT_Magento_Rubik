<?php

namespace Bat\QuoteGraphQl\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data as CustomerBalanceHelper;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyData;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepositoryInterfaceObj;
use Zend_Log_Exception;

/**
 * Helper class for place order
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $_scopeConfig;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var BalanceFactory
     */
    private BalanceFactory $balanceFactory;

    /**
     * @var CustomerBalanceHelper
     */
    private CustomerBalanceHelper $customerBalanceHelper;

    /**
     * @var OrderFrequencyData
     */
    private OrderFrequencyData $orderFrequencyData;

    /**
     * @var CustomerRepositoryInterfaceObj
     */
    private CustomerRepositoryInterfaceObj $customerRepositoryObj;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param BalanceFactory $balanceFactory
     * @param CustomerBalanceHelper $customerBalanceHelper
     * @param OrderFrequencyData $orderFrequencyData
     * @param CustomerRepositoryInterfaceObj $customerRepositoryObj
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        PriceCurrencyInterface $priceCurrency,
        BalanceFactory $balanceFactory,
        CustomerBalanceHelper $customerBalanceHelper,
        OrderFrequencyData $orderFrequencyData,
        CustomerRepositoryInterfaceObj $customerRepositoryObj
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->priceCurrency = $priceCurrency;
        $this->balanceFactory = $balanceFactory;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->orderFrequencyData = $orderFrequencyData;
        $this->customerRepositoryObj = $customerRepositoryObj;
    }

    /**
     * Check payment overdue for non-credit customer
     *
     * @param int $customerId
     * @return bool
     */
    public function checkPaymentOverDue($customerId)
    {
        $order = $this->orderFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['nin' => ['canceled']])
            ->addFieldToFilter('eda_order_type', ['nin' => ['ZLOB', 'ZRE1', 'IRO']])
            ->setOrder('created_at', 'DESC')->getFirstItem();
        if (!empty($order)) {
            if ($order->getStatus() == 'pending' && $order->getTotalDue()) {
                return true;
            }
        }
        $customer = $this->customerRepositoryObj->getById($customerId);
        if($this->customerBalanceHelper->getCustomerStoreCreditIsOverdueStatus($customer)){
            return true;
        }
        return false;
    }

    /**
     * Return payment overdue message
     *
     * @return mixed
     */
    public function getOverDueMessage()
    {
        return $this->_scopeConfig->getValue("bat_customer/registration/payment_overdue_message");
    }

    /**
     * Get Customer Remaining AR limit
     *
     * @param int $customerId
     * @param float|int $totalArLimit
     * @return int|mixed
     * @throws GraphQlNoSuchEntityException
     * @throws Zend_Log_Exception
     */
    public function getRemainingArLimit($customerId, $totalArLimit)
    {
        $totalDue = 0;
        $order = $this->customerBalanceHelper->getCustomerOrder($customerId);
        foreach ($order as $orderItem) {
            $totalDue = $totalDue + $orderItem['total_due'];
        }
        if ($totalDue == 0) {
            return $totalArLimit;
        } elseif ($totalDue) {
            $overDueMessage = $this->getOverDueMessage();
            $this->logUnsuccessfulOrder($overDueMessage, $customerId);
            throw new GraphQlNoSuchEntityException(
                __(
                    $this->getOverDueMessage()
                )
            );
        } else {
            $remainingArLimit = $totalArLimit - $totalDue;
            return $remainingArLimit;
        }
    }

    /**
     * Check order frequency
     *
     * @param CustomerInterface $customer
     * @return false|mixed
     */
    public function canPlaceOrder($customer)
    {
        $placeOrder = false;
        $result = $this->orderFrequencyData->getOrderFrequency($customer);
        if (isset($result['success'])) {
            $placeOrder = $result['success'];
        }
        return $placeOrder;
    }

    /**
     * Order Unsuccessfull Log
     *
     * @param string $message
     * @param int $customerId
     * @throws Zend_Log_Exception
     */
    public function logUnsuccessfulOrder($message, $customerId = null)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/order.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('========================================');
        $logger->info('Order place attempted by customer Id - '.$customerId);
        $logger->info('Order failed due to - '.$message);
    }
}
