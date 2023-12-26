<?php
namespace Bat\CustomerBalanceGraphQl\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\CustomerBalance\Model\BalanceFactory;
use Bat\CustomerBalance\Helper\Data as CustomerBalanceData;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var CustomerBalanceData
     */
    protected $customerBalanceHelper;

    /**
     * Default config path
     */
    public const FIRST_ORDER_PRICE_TAG_PACKAGE_SKU = 'bat_adminbulkorder/adminbulkorder/first_order_price_tag';

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * @param CollectionFactory           $orderCollectionFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param BalanceFactory              $balanceFactory
     * @param CustomerBalanceData         $customerBalanceHelper
     * @param Context                     $context
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        BalanceFactory $balanceFactory,
        CustomerBalanceData $customerBalanceHelper,
        Context $context
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->balanceFactory = $balanceFactory;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * Get Customer order
     *
     * @param  int $customerId
     * @return array
     */
    public function getCustomerOrder($customerId)
    {
        $customerOrder = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['nin' => ['canceled']])
            ->addFieldToFilter('eda_order_type', ['nin' => ['ZLOB', 'ZRE1', 'IRO']]);
        return $customerOrder->getData();
    }

    /**
     * Get Customer Remaining AR limit
     *
     * @param  int $customerId
     * @return float|int
     */
    public function getRemainingArLimit($customerId)
    {
        $remainingArLimit = 0;
        if ($this->isCreditCustomer($customerId)) {
            $totalArLimit = $this->getTotalArLimit($customerId);
            $totalDue = 0;
            $order = $this->getCustomerOrder($customerId);
            foreach ($order as $orderItem) {
                $totalDue = $totalDue + $orderItem['total_due'];
            }
            if ($totalDue == 0) {
                return $totalArLimit;
            } elseif ($totalDue > $totalArLimit) {
                $remainingArLimit = $totalDue - $totalArLimit;
            } else {
                $remainingArLimit = $totalArLimit - $totalDue;
                return $remainingArLimit;
            }
        }
        return $remainingArLimit;
    }

    /**
     * Get Customer Total AR limit
     *
     * @param  int $customerId
     * @return float|int
     */
    public function getTotalArLimit($customerId)
    {
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        $totalArLimit = 0;
        if (isset($customerCustomAttributes['total_ar_limit'])) {
            $totalArLimit = $customerCustomAttributes['total_ar_limit'];
            if ($totalArLimit->getAttributecode() == "total_ar_limit") {
                if ($totalArLimit->getValue()) {
                    $totalArLimit = $totalArLimit->getValue();
                }
            }
        }
        return $totalArLimit;
    }

    /**
     * Get Is Customer First Order
     *
     * @param int $customerId
     * @param int|null $orderId
     * @return boolean
     */
    public function getIsCustomerFirstOrder($customerId, $orderId = null)
    {
        $customerOrder = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['nin' => ['canceled','incomplete','zlob_complete']]);
        if ($orderId != null) {
            $customerOrder->addFieldToFilter('entity_id', ['nin' => [$orderId]]);
        }
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        if (isset($customerCustomAttributes['is_migrated'])) {
            $isMigrated = $customerCustomAttributes['is_migrated'];
            if ($isMigrated->getValue()) {
                return false;
            } else {
                return ($customerOrder->getSize() > 0) ? false : true;
            }
        } else {
            return ($customerOrder->getSize() > 0) ? false : true;
        }
    }

    /**
     * Get is credit customer
     *
     * @param int $customerId
     * @return boolean
     */
    public function isCreditCustomer($customerId)
    {
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        if (isset($customerCustomAttributes['is_credit_customer'])) {
            $isCreditCustomer = $customerCustomAttributes['is_credit_customer'];
            if ($isCreditCustomer->getAttributecode() == "is_credit_customer"
            && !empty($isCreditCustomer->getValue())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Customer Cart Summary
     *
     * @param int $customerId
     * @param int $websiteId
     * @param int $grandTotal
     * @return array
     */
    public function getCustomerCartSummary($customerId, $websiteId, $grandTotal)
    {
        $totalBalance = $this->customerBalanceHelper->getUsedCreditFromOrders(false,$customerId,$websiteId);
        $totalBalance = ($totalBalance < 0) ? 0 : $totalBalance;
        if ($this->isCreditCustomer($customerId)) {
            $totalArLimit = $this->getTotalArLimit($customerId);
            $overPayment = 0;
            if ($totalBalance > $totalArLimit) {
                $overPayment = $totalBalance - $totalArLimit;
                $balance = $totalArLimit;
            } else {
                $balance = $totalBalance;
            }
            $minimumPayment = 0;
            if ($grandTotal > $totalBalance) {
                $minimumPayment = $grandTotal - $totalBalance;
            }
            $grandTotal = $grandTotal - $overPayment;
            if ($grandTotal < 0) {
                $grandTotal = 0;
            }

            return ['remaining_ar' => $balance,
                   'overpayment' => $overPayment,
                   'minimum_payment' => $minimumPayment,
                   'grand_total' => $grandTotal,
                   'is_credit' => 1
                   ];
        } else {
            if ($grandTotal < $totalBalance) {
                $totalBalance = $grandTotal;
            }
            $grandTotal = $grandTotal - $totalBalance;
            return ['overpayment' => $totalBalance,
                'grand_total' => $grandTotal,
                'is_credit' => 0
                ];
        }
    }

    /**
     * Get First Order Price Tag Item
     */
    public function getFirstOrderPriceTagItem()
    {
        return $this->getConfig(self::FIRST_ORDER_PRICE_TAG_PACKAGE_SKU);
    }

    /**
     * Get Config path
     *
     * @param string $path
     * @return string|int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return customer store credit is_overdue status value
     *
     * @param CustomerInterface $customer
     * @return string|int
     * @throws LocalizedException
     */
    public function getCustomerStoreCreditIsOverdueStatus($customer)
    {
        $balance = $this->balanceFactory->create()
            ->setCustomerId($customer->getId())
            ->setWebsiteId($customer->getWebsiteId());
        return $balance->loadbyCustomer()->getOverdueFlag();
    }
}
