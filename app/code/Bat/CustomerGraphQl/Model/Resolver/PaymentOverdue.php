<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\OrderFrequencyDate;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Bat\BulkOrder\Model\ValidateCSVdata;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\SalesGraphQl\Model\OrderPaymentDeadline;

class PaymentOverdue implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CollectionFactoryInterface
     */
    private $orderCollectionFactory;

    /**
     * @var OrderFrequencyDate
     */
    private $orderFrequencyDate;

    /**
     * @var CustomerFactory
     */
    protected CustomerFactory $customerFactory;

     /**
     * @var ValidateCSVdata
     */
    private $validateCsvData;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var OrderPaymentDeadline
     */
    protected $orderPaymentDeadline;

    /**
     * Construct method
     *
     * @param GetCustomer $getCustomer
     * @param OrderFactory $orderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param BalanceFactory $balanceFactory
     * @param Data $helper
     * @param CollectionFactoryInterface $orderCollectionFactory
     * @param OrderFrequencyDate $orderFrequencyDate
     * @param CustomerFactory $customerFactory
     * @param ValidateCSVdata $validateCsvData
     * @param TimezoneInterface $timezoneInterface
     * @param OrderPaymentDeadline $orderPaymentDeadline
     */
    public function __construct(
        GetCustomer $getCustomer,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        BalanceFactory $balanceFactory,
        Data $helper,
        CollectionFactoryInterface $orderCollectionFactory,
        OrderFrequencyDate $orderFrequencyDate,
        CustomerFactory $customerFactory,
        ValidateCSVdata $validateCsvData,
        TimezoneInterface $timezoneInterface,
        OrderPaymentDeadline $orderPaymentDeadline
    ) {
        $this->getCustomer = $getCustomer;
        $this->orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->balanceFactory = $balanceFactory;
        $this->helper = $helper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderFrequencyDate = $orderFrequencyDate;
        $this->customerFactory = $customerFactory;
        $this->validateCsvData = $validateCsvData;
        $this->timezoneInterface = $timezoneInterface;
        $this->orderPaymentDeadline = $orderPaymentDeadline;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $store = $context->getExtensionAttributes()->getStore();
        $customer = $this->getCustomer->execute($context);
        $customerType = 0;
        $vbaChangeApproved = 0;
        $customerStatus = 0;
        $message = __('No overdue');
        $status = true;
        $orderStatus = false;
        $failedOrderMessage = '';
        $outletId = '';
        if ($customer->getCustomAttribute('is_credit_customer') != '') {
            $customerType = $customer->getCustomAttribute('is_credit_customer')->getValue();
        }
        $customerId = $customer->getId();
        if ($customer->getCustomAttribute('approval_status') != '') {
            $customerStatus = $customer->getCustomAttribute('approval_status')->getValue();
        }
        $nextOrderdate = $this->orderFrequencyDate->getNextOrderDate($customer);
        $regularNextOrderdate = $this->orderFrequencyDate->getNextOrderRegularFrequencyDate($customer);
        $dueAmount = 0;
        $orders = $this->orderFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('customer_id', $customerId)
                    ->addFieldToFilter('status', ['nin' => ['canceled']])
                    ->addFieldToFilter('eda_order_type', ['nin' => ['ZLOB', 'ZRE1', 'IRO']])
                    ->setOrder('created_at', 'DESC');
        foreach($orders as $order){
            if ($order->getStatus() == 'pending' || $order->getTotalDue() > 0) {
                /* Overdue Message */
                $message = $this->_scopeConfig->getValue("bat_customer/registration/payment_overdue_message");
                $dueAmount = number_format($order->getGrandTotal(), 0, '.', '');
                $status = false;
            }
        }
        if($this->helper->getCustomerStoreCreditIsOverdueStatus($customer)) {
            $status = false;
        }

        if($customer->getCustomAttribute('outlet_id')){
            $outletId = $customer->getCustomAttribute('outlet_id')->getValue();
        }
        $failedOrderMessage = $this->validateCsvData->getCustomerFailedOrders($outletId);
        if($failedOrderMessage == ''){
            $orderStatus = false;
        }
        else{
            $orderStatus = true;
        }
        $remainingBalance = $this->balanceFactory->create()
            ->setCustomerId($customerId)
            ->setWebsiteId($store->getWebsiteId())
            ->loadByCustomer()
            ->getAmount();

        $minimumAmount = 0;
        if ($customerType == 1) {
            $overpaymentvalue = 0;
            $totalArLimit = $this->helper->getTotalArLimit($customerId);
            if($remainingBalance > $totalArLimit){
                $overpaymentvalue = $remainingBalance - $totalArLimit;
            }
            $orders = $this->getOrderCollectionByCustomerId($customerId);
            $subTotal = 0;
            $grandTotal = 0;
            foreach ($orders as $order) {
                $grandTotal = $order->getGrandTotal();
                $subTotal = $grandTotal + $subTotal;
            }
            if($grandTotal > $remainingBalance){
                $minimumAmount = $grandTotal - $remainingBalance;
            }
        }
        else{
            $overpaymentvalue = $remainingBalance;
        }
        if ($customer->getCustomAttribute('is_vba_change_approved') != '') {
            $customerFactory = $this->customerFactory->create()->load($customerId);
            $customerDataModel = $customerFactory->getDataModel();
            $customerDataModel->setWebsiteId($store->getWebsiteId());
            $vbaChangeApproved = $customer->getCustomAttribute('is_vba_change_approved')->getValue();
            if ($vbaChangeApproved == 1 && $customerStatus == 1) {
                $customerDataModel->setCustomAttribute('is_vba_change_approved', 0);
                $customerFactory->updateData($customerDataModel);
                $customerFactory->save();
            }
            if ($vbaChangeApproved == 2 && $customerStatus == 1) {
                $customerDataModel->setCustomAttribute('is_vba_change_approved', 0);
                $customerFactory->updateData($customerDataModel);
                $customerFactory->save();
            }
        }

        if($status == false) {
            $status = $this->getOrderPaymentDeadlineDate($customerId, $status);
        }

        $result = [
            'customer_id' => $customerId,
            'status' => $status,
            'message' => $message,
            'due_amount' => $dueAmount,
            'total_overpayment' => $overpaymentvalue,
            'minimum_payment' => $minimumAmount,
            'next_order_date' => $nextOrderdate,
            'order_regular_frequency_date' => $regularNextOrderdate,
            'customer_status' => $customerStatus,
            'is_vba_change_approved' => $vbaChangeApproved,
            'failed_orders' => $orderStatus,
        ];
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getTotalRemainingArLimit($customerId)
    {
        $totalArLimit = $this->helper->getTotalArLimit($customerId);
        $totalDue = 0;
        $order = $this->helper->getCustomerOrder($customerId);
        foreach ($order as $orderItem) {
            $totalDue = $totalDue + $orderItem['total_due'];
        }
        if ($totalDue == 0) {
            return $totalArLimit;
        } else {
            $remainingArLimit = $totalArLimit - $totalDue;
            return $remainingArLimit;
        }
    }

    /**
     * Getting Order Collection Function

     * @param string $customerId
     * Getting all the Processing and Pending orders for customer
     */
    public function getOrderCollectionByCustomerId($customerId)
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addFieldToSelect('*');
        $orderCollection->addAttributeToFilter('customer_id', $customerId);
        $orderCollection->addAttributeToFilter('status', ['in' => ['processing', 'pending']]);

        return $orderCollection;
    }

    /**
     * Getting Order Collection Function

     * Getting all the Pending orders for customer
     */
    public function getOrderPaymentDeadlineDate($customerId) {

        $currentDate = $this->timezoneInterface->date()->format('Y-m-d');
        $currentTime = $this->timezoneInterface->date()->format('H:i:s');

        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToSelect('*');
        $collection->addAttributeToFilter('customer_id', $customerId);
        $collection->addAttributeToFilter('status', ['in' => ['pending']]);
        if ($collection->count()) {
            foreach ($collection as $key => $orderCollection) {
                $orderPaymentDeadline = $this->orderPaymentDeadline->
                getPaymentDeadline($orderCollection['entity_id']);
                $orderPaymentDeadline = date('Y-m-d', strtotime($orderPaymentDeadline));
                if ($orderPaymentDeadline == $currentDate) {
                    $storedTime = $this->_scopeConfig->getValue("payment_deadline/general/payment_overdue_message_time");
                    if($currentTime >= $storedTime) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}
