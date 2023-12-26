<?php
namespace Bat\CustomerBalance\Model\Api;

use Bat\CustomerBalance\Api\CustomerCreditUpdateInterface;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Sales\Model\BatOrderStatus;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Psr\Log\LoggerInterface;
use Bat\Customer\Helper\Data as CustomerHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Bat\Sales\Helper\Data as SalesHelper;
use Bat\Sales\Model\EdaOrderType;
use Bat\CustomerBalance\Helper\Data as CustomerBalanceHelper;

/**
 * @class CustomerCreditUpdate
 * Update Customer credit details
 */
class CustomerCreditUpdate implements CustomerCreditUpdateInterface
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
     * @var CustomerHelper
     */
    private CustomerHelper $customerHelper;

    /**
     * @var BalanceFactory
     */
    private BalanceFactory $_balanceFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $orderCollectionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var CustomerBalanceHelper
     */
    private CustomerBalanceHelper $customerBalanceHelper;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param CustomerHelper $customerHelper
     * @param BalanceFactory $balanceFactory
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param SalesHelper $salesHelper
     * @param KakaoSms $kakaoSms
     * @param CustomerBalanceHelper $customerBalanceHelper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        CustomerHelper $customerHelper,
        BalanceFactory $balanceFactory,
        CollectionFactory $orderCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        SalesHelper $salesHelper,
        KakaoSms $kakaoSms,
        CustomerBalanceHelper $customerBalanceHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->customerHelper = $customerHelper;
        $this->_balanceFactory = $balanceFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->salesHelper = $salesHelper;
        $this->kakaoSms = $kakaoSms;
        $this->customerBalanceHelper = $customerBalanceHelper;
    }

    /**
     * Update Customer credit
     *
     * @param string $sapOutletCode
     * @param string $outletId
     * @param string $creditLimit
     * @param string $availableCreditLimit
     * @param string $creditExposure
     * @param string $overdueFlag
     * @param string $overdueAmount
     * @return array
     */
    public function updateCustomerCredit(
        $sapOutletCode,
        $outletId,
        $creditLimit,
        $availableCreditLimit,
        $creditExposure,
        $overdueFlag,
        $overdueAmount
    ) {
        $result = [];
        try {
            $this->logCreditUpdateRequest('======================================================');
            $this->logCreditUpdateRequest('Request : ');
            $request = [
                'sap_outlet_code' => $sapOutletCode,
                'outlet_id'=>$outletId,
                'credit_limit'=>$creditLimit,
                'available_credit_limit'=>$availableCreditLimit,
                'overdue_flag'=>$overdueFlag,
                'overdue_amount'=>$overdueAmount,
                'credit_exposure'=>$creditExposure
            ];
            $this->logCreditUpdateRequest(json_encode($request));
            $this->logCreditUpdateRequest('Response : ');
            $this->validateInput(
                $sapOutletCode,
                $outletId,
                $creditLimit,
                $availableCreditLimit,
                $creditExposure,
                $overdueFlag,
                $overdueAmount
            );
            $customer = $this->customerHelper->isOutletIdValidCustomer($outletId);
            if ($customer) {
                $customer = $this->customerRepository->getById($customer->getId());
                $isOverDue = 0;
                if ($overdueFlag == 'Y') {
                    $isOverDue = 1;
                }
                $websiteId = $customer->getWebsiteId();
                $balance = $this->_balanceFactory->create()->setCustomer($customer)
                    ->setWebsiteId($websiteId);
                $balance = $balance->loadbyCustomer();
                $currentBalance = $balance->getAmount();
                $currentCreditLimit = $balance->getCreditLimit();
                $balance->setOverdueAmount($overdueAmount)
                    ->setOverdueFlag($isOverDue)
                    ->setCreditLimit($creditLimit)
                    ->setCreditExposure($creditExposure)
                    ->setAmountDelta($availableCreditLimit - $currentBalance)
                    ->setUpdatedActionAdditionalInfo('Updated by EDA API');
                $balance->save();
                $this->customerBalanceHelper->batOrderBalanceUpdate(
                    $customer->getId(),
                    $availableCreditLimit,
                    'Updated by EDA API',
                    'Updated',
                    'EDA'
                );
                $this->customerBalanceHelper->logStoreCreditBalanceChange('===================================');
                $this->customerBalanceHelper->logStoreCreditBalanceChange('Customer ID : '.$customer->getId());
                $this->customerBalanceHelper->logStoreCreditBalanceChange(
                    'Available Balance updated by EDA API : '.$availableCreditLimit
                );
                $isCreditCustomer = false;
                if ($creditLimit > 0) {
                    $isCreditCustomer = true;
                }
                $customer->setCustomAttribute('is_credit_customer', $isCreditCustomer);
                $customer->setCustomAttribute('total_ar_limit', $creditLimit);
                $this->customerRepository->save($customer);
                $customerId = $customer->getId();
                $orderCollection = $this->getOrderCollection($customerId);
                if ($orderCollection->count()) {
                    $this->processOrders($orderCollection, $customerId, $websiteId);
                }
                if ($currentCreditLimit != $creditLimit) {
                    try {
                        if ($customer->getCustomAttribute('approval_status') != '') {
                            if($customer->getCustomAttribute('approval_status')->getValue() == 1) {
                                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                                $this->kakaoSms->sendSms($mobileNumber, [], 'CreditLimitChange_001');
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logCreditUpdateRequest($e->getMessage());
                    }
                }
                $result[] = ['success' => true, 'message'=>'Customer credit updated successfully'];
            } else {
                throw new LocalizedException(__('Customer not found'));
            }
        } catch (\Exception $e) {
            $result[] = ['success' => false, 'message'=>$e->getMessage()];
        }
        $this->logCreditUpdateRequest(json_encode($result));
        return $result;
    }

    /**
     * Validate customer credit update input
     *
     * @param string $sapOutletCode
     * @param string $outletId
     * @param string $creditLimit
     * @param string $availableCreditLimit
     * @param string $creditExposure
     * @param string $overdueFlag
     * @param string $overdueAmount
     * @throws LocalizedException
     */
    public function validateInput(
        $sapOutletCode,
        $outletId,
        $creditLimit,
        $availableCreditLimit,
        $creditExposure,
        $overdueFlag,
        $overdueAmount
    ) {
        if (trim($outletId) == '') {
            throw new LocalizedException(__('outlet_id is required to update customer'));
        }
        if ($creditLimit == '') {
            throw new LocalizedException(__('credit_limit is required to update customer'));
        }
        $this->checkIsNumeric('credit_limit', $creditLimit);
        $this->checkPositiveNumber('credit_limit', $creditLimit);
        if ($availableCreditLimit == '') {
            throw new LocalizedException(__('available_credit_limit is required to update customer'));
        }
        $this->checkIsNumeric('available_credit_limit', $availableCreditLimit);
        if ($creditExposure == '') {
            throw new LocalizedException(__('credit_exposure is required to update customer'));
        }
        $this->checkIsNumeric('credit_exposure', $creditExposure);
        if ($overdueFlag == '') {
            throw new LocalizedException(__('overdue_flag is required to update customer'));
        } elseif ($overdueFlag !== 'Y' && $overdueFlag !== 'N') {
            throw new LocalizedException(__('Specify Y or N for overdue_flag.'));
        }
        if ($overdueAmount == '') {
            throw new LocalizedException(__('overdue_amount is required to update customer'));
        }
        $this->checkIsNumeric('overdue_amount', $overdueAmount);
    }

    /**
     * Check for positive number
     *
     * @param string $type
     * @param float $value
     * @throws LocalizedException
     */
    public function checkPositiveNumber($type, $value)
    {
        if ($value < 0) {
            throw new LocalizedException(__('%1 should be a positive number', $type));
        }
    }

    /**
     * Check for Numeric
     *
     * @param string $type
     * @param mixed $value
     * @throws LocalizedException
     */
    public function checkIsNumeric($type, $value)
    {
        if (!is_numeric($value)) {
            throw new LocalizedException(__('%1 should be a number', $type));
        }
    }

    /**
     * Credit update Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logCreditUpdateRequest($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaCreditUpdate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Get order collection based on pending status
     *
     * @param int $customerId
     * @return Collection
     */
    public function getOrderCollection($customerId)
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', ['eq'=>'pending'])
            ->addFieldToFilter('eda_order_type', ['eq'=>'ZOR'])
            ->setOrder('created_at', 'ASC');
    }

    /**
     * Check order is eligible for invoice
     *
     * @param Collection $orderCollection
     * @param int $customerId
     * @param int $websiteId
     * @throws LocalizedException
     */
    public function processOrders($orderCollection, $customerId, $websiteId)
    {
        $ordersProcessed = false;
        /** @var OrderInterface $order */
        foreach ($orderCollection as $order) {
            $totalDue = $order->getTotalDue();
            if ($totalDue > 0) {
                $customOrderBalance = $this->customerBalanceHelper->getUsedCreditFromOrders(
                    true,
                    $customerId,
                    $websiteId
                );
                if ($customOrderBalance >= $order->getGrandTotal()) {
                    $ordersProcessed = true;
                    $order->setBaseTotalDue(0);
                    $order->setTotalDue(0);
                    $totalPaid = $order->getTotalPaid() + $totalDue;
                    $order->setTotalPaid($totalPaid);
                    $order->setBaseTotalPaid($totalPaid);
                    $order->addCommentToStatusHistory(
                        __('Pending payment paid with store credit')
                    )->setIsCustomerNotified(false);
                    $this->orderRepository->save($order);
                    $orderType = $order->getEdaOrderType();
                    if ($orderType == EdaOrderType::ZOR) {
                        $this->salesHelper->updateOrderToEda(
                            $order->getEntityId(),
                            $orderType,
                            'SWIFTPLUS',
                            $order->getIncrementId()
                        );
                    }
                }
            }
        }
        return $ordersProcessed;
    }

    /**
     * Return store credit balance for customer
     *
     * @param  int $customerId
     * @param  int $websiteId
     * @return float
     * @throws LocalizedException
     */
    private function getStoreCreditBalance($customerId, int $websiteId): float
    {
        $baseBalance = $this->_balanceFactory->create()
            ->setCustomerId($customerId)
            ->setWebsiteId($websiteId)
            ->loadByCustomer()
            ->getAmount();
        return $baseBalance;
    }

    /**
     * Update used store credit balance for the order
     *
     * @param OrderInterface $order
     * @param float $totalDue
     * @param int $websiteId
     * @param int $customerId
     */
    public function updateStoreCreditBalanceUtilized($order, $totalDue, $websiteId, $customerId)
    {
        $balance = $this->_balanceFactory->create()
            ->setCustomerId($customerId)
            ->setWebsiteId($websiteId);
        $balance = $balance->loadbyCustomer();
        $this->customerBalanceHelper->logStoreCreditBalanceChange('=============================================');
        $this->customerBalanceHelper->logStoreCreditBalanceChange('Customer Id : '.$customerId);
        $this->customerBalanceHelper->logStoreCreditBalanceChange('Before Change Order Id : '.$order->getIncrementId());
        $this->customerBalanceHelper->logStoreCreditBalanceValues($balance);
        $balance->setAmountDelta(-$totalDue)->setHistoryAction(History::ACTION_USED)->setOrder($order);
        $balance->save();
        $this->customerBalanceHelper->logStoreCreditBalanceChange('After Change Order Id : '.$order->getIncrementId());
        $this->customerBalanceHelper->logStoreCreditBalanceValues($balance);
    }
}
