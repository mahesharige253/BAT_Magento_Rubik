<?php
namespace Bat\BulkOrder\Model;

use Bat\Sales\Model\EdaOrderType;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Bat\Customer\Helper\Data;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Bat\BulkOrder\Model\ResourceModel\BulkOrder\CollectionFactory;
use Bat\BulkOrder\Model\BulkOrderFactory;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\VirtualBank\Helper\Data as VirtualBankData;
use Bat\Kakao\Model\Sms as KakaoSms;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\Currency\Data\Currency as CurrencyData;

class ValidateCreateBulkOrder
{
    /**
     * @var QuoteFactory
     */
    private $quote;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var Data
     */
     protected $helper;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var BulkOrderFactory
     */
    protected $bulkOrderFactory;

    /**
     * @var CustomerRepositoryInterface;
     */
    protected $customerRepository;

    /**
     * @var VirtualBankData
     */
    protected $virtualBankData;

    /**
      * @var KakaoSms
      */
    private KakaoSms $kakaoSms;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Currency
     */
    protected Currency $currency;

    /**
     * @param QuoteFactory $quote
     * @param QuoteManagement $quoteManagement
     * @param Data $helper
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CollectionFactory $collectionFactory
     * @param BulkOrderFactory $bulkOrderFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param VirtualBankData $virtualBankData
     * @param KakaoSms $kakaoSms
     * @param LoggerInterface $logger
     * @param Currency $currency
     *
     */
    public function __construct(
        QuoteFactory $quote,
        QuoteManagement $quoteManagement,
        Data $helper,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CollectionFactory $collectionFactory,
        BulkOrderFactory $bulkOrderFactory,
        CustomerRepositoryInterface $customerRepository,
        VirtualBankData $virtualBankData,
        KakaoSms $kakaoSms,
        LoggerInterface $logger,
        Currency $currency
    ) {
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->helper = $helper;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->collectionFactory = $collectionFactory;
        $this->bulkOrderFactory = $bulkOrderFactory;
        $this->customerRepository = $customerRepository;
        $this->virtualBankData = $virtualBankData;
        $this->kakaoSms = $kakaoSms;
        $this->logger = $logger;
        $this->currency = $currency;
    }

    /**
     * Place Bulk Order.
     *
     * @param array $bulkOrderItems
     * @param boolean $orderConsent
     * @param int $customerId
     * @param int $storeId
     * @return array
     */
    public function placeOrder($bulkOrderItems, $orderConsent, $customerId, $storeId)
    {
        try {
            $bulkOrder = $this->getBulkOrder($storeId);
            if ($bulkOrder->getSize() >= 1) {
                $item = $bulkOrder->getLastItem();
                $bulOrderIncrement = $item->getBulkorderId() + 1;
            } else {
                $bulOrderIncrement = $storeId.'00000001';
            }

            $customerData = $this->helper->getCustomer('entity_id', $customerId)->getFirstItem();
            $customer = $this->customerRepository->getById($customerId);
            $customerCustomAttributes = $customer->getCustomAttributes();
            if (isset($customerCustomAttributes['is_parent'])) {
                $isCreditCustomer = $customerCustomAttributes['is_parent'];
                if ($isCreditCustomer->getAttributecode() == "is_parent"
                && empty($isCreditCustomer->getValue())) {
                    throw new GraphQlNoSuchEntityException(__(
                        'This customer is not parent. Please try with parent customer'
                    ));
                }
            }
            $this->getValidateCustomer($bulkOrderItems);
            $virtualBank = $customer->getCustomAttribute('virtual_bank')->getValue();
            $bankName = $this->virtualBankData->getVirtualBankName($virtualBank);
            $virtualAccountNumber =  $customer->getCustomAttribute('virtual_account')->getValue();
            $parentOutletId = $customerData['outlet_id'];
            $maskedHashId = '';
            foreach ($bulkOrderItems as $bulkOrderItem) {
                $maskedHashId = $bulkOrderItem['masked_cart_id'];
                $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedHashId);
                $quote = $this->quote->create()->load($cartId);
                $quote->setEdaOrderType(EdaOrderType::ZOR);
                $quote->collectTotals();
                if (empty($quote->getIsActive()) || $quote->getIsActive() == 0) {
                    throw new GraphQlNoSuchEntityException(__(
                        $maskedHashId.' "masked_cart_id" is not active. try with different'
                    ));
                }

                // Create Order From Quote Object
                $order = $this->quoteManagement->submit($quote);
                $orderId = $order->getIncrementId();
                $customerId = $order->getCustomerId();
                $this->bulkOrderKakaoMessage($order, $customerId);

                $order->setData('is_bulk_order', 1);
                $order->setData('order_consent', $orderConsent);
                $order->setData('outlet_id', $bulkOrderItem['outlet_id']);
                $order->setData('parent_outlet_id', $parentOutletId);
                $order->setData('bulkorder_id', $bulOrderIncrement);
                $order->save();
                $bulkOrder = $this->bulkOrderFactory->create();
                $bulkOrder->setData('bulkorder_id', $bulOrderIncrement);
                $bulkOrder->setData('increment_id', $orderId);
                $bulkOrder->setData('parent_outlet_id', $parentOutletId);
                $bulkOrder->setData('bankname', $bankName);
                $bulkOrder->setData('virtual_account', $virtualAccountNumber);
                $bulkOrder->setData('store_id', $storeId);
                $bulkOrder->save();
            }

            return ['bulkorder_id' => $bulOrderIncrement];

        } catch (Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

    /**
     * Get Bulk Order.
     *
     * @param int $storeId
     * @return object
     */
    public function getBulkOrder($storeId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('store_id', $storeId);
        return $collection;
    }

    /**
     * Get Validate Customer
     *
     * @param array $bulkOrderItems
     * @return null|Exception
     */
    public function getValidateCustomer($bulkOrderItems)
    {
        foreach ($bulkOrderItems as $bulkOrderItem) {
             $customerData = $this->helper->getCustomer('outlet_id', $bulkOrderItem['outlet_id'])->getFirstItem();
             $customer = $this->customerRepository->getById($customerData->getEntityId());
             $approvalStatus = $customer->getCustomAttribute('approval_status')->getValue();
            if ($approvalStatus == 0) {
                throw new GraphQlNoSuchEntityException(__(
                    'Your customer account is not approved.'
                ));
            }
            $closureValue = ['6','7','8','9','10','11'];
            if ($disclosureApprovalStatus = $customer->getCustomAttribute(
                'approval_status'
            )) {
                if (in_array($disclosureApprovalStatus->getValue(), $closureValue)) {
                    throw new GraphQlNoSuchEntityException(
                        __("You can't place order. Your account is under deactivation")
                    );
                }
            }
        }
    }

    /**
     * Get Bulk Order.
     *
     * @param array $orderId
     * @param string $customerId
     * @return null
     */
    public function bulkOrderKakaoMessage($order, $customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            if ($customer->getCustomAttribute('mobilenumber')) {
                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                $orderCreatedDate = date('Y-m-d', strtotime($order->getCreatedAt()));

                $firstItemName = $vbaBankInfo = '';
                $orderQty = 0;
                $i= 0;
                foreach ($order->getAllItems() as $item) {
                    if ($item->getIsPriceTag() == 0) {
                        if ($i == 0) {
                            $firstItemName = $item->getName();
                        }
                        $orderQty += $item->getQtyOrdered();
                        $i++;
                    }
                }
                if ($i > 1) {
                    $firstItemName = $firstItemName.' 외 '.(--$i).' 개';
                }

                if($customer->getCustomAttribute('virtual_bank') && $customer->getCustomAttribute('virtual_account')) {
                    $vbaBankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
                    $vbaBankNumber = $customer->getCustomAttribute('virtual_account')->getValue();
                    $vbaBankInfo = $this->virtualBankData->getVirtualBankName($vbaBankCode).', '.$vbaBankNumber;
                }

                /* Kakao SMS for order placed */
                $params = [
                    'salesorder_number' => $order->getIncrementId(),
                    'salesorder_date' => $orderCreatedDate,
                    '1stsalesorderproduct_others' => $firstItemName,
                    'totalsalesorder_qty' => $orderQty,
                    'totalsalesorder_amount' => $this->currency->format($order->getSubtotalInclTax(), ['display'=> CurrencyData::NO_SYMBOL, 'precision' => 0], false),
                    'vbabank_vbanumber' => $vbaBankInfo
                ];
                $this->kakaoSms->sendSms($mobileNumber, $params, 'SalesOrder_001');
            }
        }
        catch (Exception $e) {
            $this->logger->error('Bulk order kakao message failed');
            $this->logger->error($e->getMessage());
        }
    }
}
