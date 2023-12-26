<?php
namespace Bat\QuoteGraphQl\Observer;

use Bat\Sales\Model\EdaOrderType;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Bat\CustomerBalance\Helper\Data as CustomerBalance;
use Magento\Store\Model\StoreManagerInterface;
use Bat\BulkOrder\Model\Resolver\CartData;
use Psr\Log\LoggerInterface;

/**
 * @class AddOrderConsent
 * Add order consent to sales order
 */
class AddOrderConsent implements ObserverInterface
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CartData
     */
     protected $cartData;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CustomerBalance
     */
    private CustomerBalance $customerBalance;

    /**
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param CartData $cartData
     * @param LoggerInterface $logger
     * @param CustomerBalance $customerBalance
     */
    public function __construct(
        Data $helper,
        StoreManagerInterface $storeManager,
        CartData $cartData,
        LoggerInterface $logger,
        CustomerBalance $customerBalance
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->cartData = $cartData;
        $this->logger = $logger;
        $this->customerBalance = $customerBalance;
    }

    /**
     * Set order consent status
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        if ($websiteId == 0) {
            $websiteId = 1;
        }
        $quote = $observer->getQuote();
        $cartPricesData = $this->cartData->getCartPrices($quote);
        $orderType = $quote->getEdaOrderType();
        $order = $observer->getOrder();
        if ($orderType == 'ZOR') {
            $orderSummary = $this->helper->getCustomerCartSummary(
                $observer->getQuote()->getCustomerId(),
                $websiteId,
                $cartPricesData['grand_total']
            );
            if ($orderSummary['is_credit']) {
                $order->setData('remaining_ar', $orderSummary['remaining_ar']);
            }
            $order->setData('over_payment', $orderSummary['overpayment']);
            $order->setData('order_grand_total', $orderSummary['grand_total']);
            $customOrderBalance = $this->customerBalance->getUsedCreditFromOrders(
                false,
                $order->getCustomerId()
            );
            $magentoGrandTotal = $order->getGrandTotal();
            if ($customOrderBalance >= $magentoGrandTotal) {
                $order->setBaseGrandTotal($magentoGrandTotal);
                $order->setBaseTotalDue(0);
                $order->setTotalDue(0);
                $order->setTotalPaid($magentoGrandTotal);
                $order->setBaseTotalPaid($magentoGrandTotal);
            } else {
                $order->setBaseGrandTotal($magentoGrandTotal);
                $totalDue = $magentoGrandTotal - $customOrderBalance;
                $order->setTotalDue($totalDue);
                $order->setBaseTotalDue($totalDue);
                $order->setTotalPaid($customOrderBalance);
                $order->setBaseTotalPaid($customOrderBalance);
            }
        }
        $order->setData('order_consent', $quote->getOrderConsent());
        $order->setData('eda_order_type', $orderType);
        $order->setData('is_return_order', $quote->getIsReturnOrder());
        return $this;
    }
}
