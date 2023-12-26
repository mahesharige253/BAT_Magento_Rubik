<?php

namespace Bat\Sales\Model;

use Bat\Discount\Model\Source\DiscountRuleType;
use Bat\Integration\Helper\Data as IntegrationHelper;
use Bat\Sales\Helper\Data;
use Bat\Sales\Model\EdaOrderType;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource\CollectionFactory as EdaOrdersCollectionFactory;
use Magento\SalesRule\Model\RuleFactory;
use Psr\Log\LoggerInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data as CustomerBalanceHelper;

/**
 * @class SendOrderDetails
 * Create/Update Orders in EDA
 */
class SendOrderDetails
{
    protected const ORDER_STATUS_UPDATE_EDA_PATH = 'bat_integrations/bat_order/order_status_required_to_update_eda';
    private const EDA_ORDER_ENDPOINT_PATH = 'bat_integrations/bat_order/eda_order_endpoint';
    private const LOG_ENABLED_PATH = 'bat_integrations/bat_order/eda_order_log';

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var ProductRepository
     */
    protected ProductRepository $productRepository;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $orderCollectionFactory;

    /**
     * @var Data
     */
    protected Data $dataHelper;

    /**
     * @var EdaOrdersCollectionFactory
     */
    protected EdaOrdersCollectionFactory $edaOrdersCollectionFactory;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var RuleFactory
     */
    private RuleFactory $rule;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializerInterface;

    /**
     * @var CustomerBalanceHelper
     */
    private CustomerBalanceHelper $customerBalanceHelper;

    /**
     * @var IntegrationHelper
     */
    private IntegrationHelper $integrationHelper;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepository $productRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param Data $dataHelper
     * @param EdaOrdersCollectionFactory $edaOrdersCollectionFactory
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param EdaOrdersResource $edaOrdersResource
     * @param LoggerInterface $logger
     * @param CompanyManagementInterface $companyManagement
     * @param RuleFactory $rule
     * @param SerializerInterface $serializerInterface
     * @param CustomerBalanceHelper $customerBalanceHelper
     * @param IntegrationHelper $integrationHelper
     * @param ManagerInterface $messageManager
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ProductRepository $productRepository,
        CollectionFactory $orderCollectionFactory,
        Data $dataHelper,
        EdaOrdersCollectionFactory $edaOrdersCollectionFactory,
        EdaOrdersFactory $edaOrdersFactory,
        EdaOrdersResource $edaOrdersResource,
        LoggerInterface $logger,
        CompanyManagementInterface $companyManagement,
        RuleFactory $rule,
        SerializerInterface $serializerInterface,
        CustomerBalanceHelper $customerBalanceHelper,
        IntegrationHelper $integrationHelper,
        ManagerInterface $messageManager,
        TimezoneInterface $timezoneInterface
    ) {
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->edaOrdersCollectionFactory = $edaOrdersCollectionFactory;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->logger = $logger;
        $this->companyManagement = $companyManagement;
        $this->rule = $rule;
        $this->serializerInterface = $serializerInterface;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->integrationHelper = $integrationHelper;
        $this->messageManager = $messageManager;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * Prepare Payload for EDA Order create/update
     *
     * @param OrderInterface $order
     * @param string $channel
     */
    public function formatOrderData($order, $channel)
    {
        $orderType = $order->getEdaOrderType();
        $customerId = $order->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $sapOutletCode = '';
        if ($customer->getCustomAttribute('sap_outlet_code')) {
            $sapOutletCode = $customer->getCustomAttribute('sap_outlet_code')->getValue();
        }
        $createdAt = $this->dataHelper->formatDate($order->getCreatedAt());
        $deliveryDate = $this->dataHelper->getDeliveryDate();
        $result = [];
        $orderData = [];
        $result['header']['batchId'] = $this->dataHelper->getUniqueBatchId($order->getEntityId());
        $transactionType = 'SLABT';
        if ($orderType == EdaOrderType::ZLOB) {
            $transactionType = 'MRABT';
        } elseif ($orderType == EdaOrderType::ZREONE) {
            $transactionType = 'CNORD';
        }
        if ($order->getStatus() == BatOrderStatus::DELIVERY_FAILED_STATUS) {
            $transactionType = 'MRABT';
        }
        $result['header']['transactionType'] = $transactionType;
        $result['header']['creationDate'] = $createdAt;
        $result['header']['countryCode'] = $this->dataHelper->getSystemConfigValue('general/country/default');
        $result['header']['channel'] = $channel;
        $orderNumber = $order->getIncrementId();
        if ($channel == 'OMS' && ($orderType == EdaOrderType::ZOR || $orderType == EdaOrderType::ZLOB)) {
            if ($order->getSapOrderNumber() != '') {
                $orderNumber = $order->getSapOrderNumber();
            }
        }
        $orderData['orderNumber'] = $orderNumber;
        $orderData['orderType'] = $orderType;
        $orderData['salesOrg'] = 'KR12';
        $orderData['distributionChannel'] = '01';
        $orderData['soldToCustomer'] = $sapOutletCode;
        $orderData['shipToCustomer'] = $sapOutletCode;
        $orderData['division'] = '01';
        $orderData['deliveryDate'] = ($orderType == EdaOrderType::ZOR) ? $deliveryDate : $createdAt;
        $orderData['outletId'] = $customer->getCustomAttribute('outlet_id')->getValue();
        $orderData['outletName'] = $this->getCompanyName($customerId);
        $returnOriginalOrderNumber = $order->getReturnOriginalOrderId();
        if ($orderType == EdaOrderType::ZLOB) {
            $orderData['originalOrderNumber'] = $returnOriginalOrderNumber;
        }
        if ($orderType == EdaOrderType::ZREONE && $channel == 'OMS') {
            $orderData['iroRefNumber'] = $returnOriginalOrderNumber;
        }
        $orderData['orderSystem'] = "WEB";
        $orderData['poType'] = "019";
        $orderData['createdDate'] = $createdAt;
        if ($orderType == EdaOrderType::ZREONE) {
            $orderData['orderReason'] = (string)$order->getReturnSwiftReason();
        } elseif ($orderType == EdaOrderType::IRO) {
            $orderData['orderReason'] = $order->getReturnSwiftCode();
        }
        $orderData['purchaseOrderType'] = '';
        $orderData['lineItems'] = [];
        $orderItems = $order->getAllItems();
        $i = 1;
        $totalQty = 0;
        $appliedRules = $this->getDiscountData($order);
        $totalSpecialDiscounts = abs($order->getDiscountAmount());
        $discountTypeApplied = $appliedRules['discount_type'];
        $discountLabel = $appliedRules['discount_type_code'];
        $isSkuNpiDiscount = true;
        if ($discountTypeApplied != 0) {
            if ($appliedRules['discount_type'] != 4) {
                $isSkuNpiDiscount = false;
            }
        }
        $notSkuNpiDiscount = 0;
        if (!$isSkuNpiDiscount) {
            $notSkuNpiDiscount = $appliedRules['discount_data']['other_discounts']['discount_amount'];
        }
        $notSkuNpiDiscountUpdated = false;
        foreach ($orderItems as $item) {
            if (($channel == 'SWIFTPLUS') && $item->getIsPriceTag()) {
                continue;
            }
            $lineItemData = [];
            $lineItemData['lineItemId'] = $i++;
            $lineItemData['sapProductCode'] = $item->getSku();
            $baseToSecondaryUom = (float)$item->getBaseToSecondaryUom();
            if ($baseToSecondaryUom <= 0 || $baseToSecondaryUom == '') {
                $baseToSecondaryUom = 1;
            }
            $lineItemData['quantity'] = (float)( $item->getQtyOrdered() * $baseToSecondaryUom )/1000;
            $totalQty = $totalQty + $lineItemData['quantity'];
            $lineItemData['uom'] = $item->getUom();
            if ($orderType == EdaOrderType::ZREONE) {
                $lineItemData['returnReason'] = $order->getReturnSwiftCode();
            } elseif ($orderType == EdaOrderType::IRO || $orderType == EdaOrderType::ZLOB) {
                $lineItemData['returnReason'] = (string)$order->getReturnSwiftReason();
            }
            $taxPercent = 1.1;
            $discountAmount = $item->getDiscountAmount();
            $itemPrice = $item->getRowTotal();
            $vat = 0;
            if (!$notSkuNpiDiscountUpdated && !$isSkuNpiDiscount &&
                $notSkuNpiDiscount > 0 && !$item->getIsPriceTag() && $discountTypeApplied != 0
            ) {
                if ($discountTypeApplied != 1) {
                    if (($itemPrice - $notSkuNpiDiscount) > $notSkuNpiDiscount) {
                        $notSkuNpiDiscountUpdated = true;
                        $lineItemData['discounts'][] = [
                            'discountSeqNo' => 1,
                            'discountType' => $discountLabel,
                            'discountValue' => (float)$notSkuNpiDiscount
                        ];
                        $disRetailerPrice = $itemPrice - $notSkuNpiDiscount;
                        $itemSalesPrice = $disRetailerPrice / $taxPercent;
                        $vat = $disRetailerPrice - $itemSalesPrice;
                        $itemPrice = $itemPrice - $notSkuNpiDiscount;
                    }
                } else {
                    if (($itemPrice - $totalSpecialDiscounts) > $totalSpecialDiscounts) {
                        $notSkuNpiDiscountUpdated = true;
                        $lineItemData['discounts'][] = [
                            'discountSeqNo' => 1,
                            'discountType' => $discountLabel,
                            'discountValue' => (float)$totalSpecialDiscounts
                        ];
                        $disRetailerPrice = $itemPrice - $totalSpecialDiscounts;
                        $itemSalesPrice = $disRetailerPrice / $taxPercent;
                        $vat = $disRetailerPrice - $itemSalesPrice;
                        $itemPrice = $itemPrice - $totalSpecialDiscounts;
                    }
                }
            }
            if ($isSkuNpiDiscount && !$notSkuNpiDiscountUpdated && $discountTypeApplied != 0) {
                if (isset($appliedRules['discount_data']['sku_npi'][$item->getSku()])) {
                    $skuNpiDiscountAmount = $appliedRules['discount_data']['sku_npi'][$item->getSku()]
                    ['discount_amount'];
                    $lineItemData['discounts'][] = [
                        'discountSeqNo' => 1,
                        'discountType' => $discountLabel,
                        'discountValue' => (float)$skuNpiDiscountAmount
                    ];
                    $disRetailerPrice = $itemPrice - $skuNpiDiscountAmount;
                    $itemSalesPrice = $disRetailerPrice / $taxPercent;
                    $vat = $disRetailerPrice - $itemSalesPrice;
                    $itemPrice = $itemPrice - $skuNpiDiscountAmount;
                }
            }
            if ($vat <= 0) {
                $itemSalesPrice = $itemPrice / $taxPercent;
                $vat = $itemPrice - $itemSalesPrice;
            }
            $lineItemData['netAmount'] = (float)$itemPrice;
            $lineItemData['tax'] = floor($vat);
            $orderData['lineItems'][] = $lineItemData;
        }
        $result['orders'][] = $orderData;
        $result['footer']['recordCount'] = 1;
        $result['footer']['totalQty'] = $totalQty;
        return $result;
    }

    /**
     * Return order collection for EDA create order
     *
     * @param string $orderId
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     * @throws LocalizedException
     */
    public function getOrderCollection($orderId)
    {
        $orderCollection = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $orderId]);
        return $orderCollection;
    }

    /**
     * Return EDA create/update orders collection
     *
     * @param string $maxFailuresAllowed
     * @return EdaOrdersResource\Collection
     */
    public function getEdaOrderCollection($maxFailuresAllowed)
    {
        $currentTime = $this->timezoneInterface->date()->modify('-3 Minutes')->format('Y-m-d H:i:s');
        $currentTime = $this->timezoneInterface->convertConfigTimeToUtc($currentTime);
        return $this->edaOrdersCollectionFactory->create()->addFieldToSelect('*')
            ->addFieldToFilter('failure_attempts', ['lt'=>$maxFailuresAllowed])
            ->addFieldToFilter('order_sent', ['eq'=>0])
            ->addFieldToFilter('created_at',['lteq'=>$currentTime]);
    }

    /**
     * Update order in EDA order update pending table
     *
     * @param string $orderId
     * @param string $orderType
     */
    public function addOrderInEdaOrderUpdate($orderId, $orderType)
    {
        try {
            $edaOrders = $this->edaOrdersCollectionFactory->create()->addFieldToSelect('*')
                ->addFieldToFilter('order_id', ['eq'=>$orderId]);
            if ($edaOrders->count()) {
                foreach ($edaOrders as $edaOrder) {
                    $edaOrder->setOrderSent(0);
                    $edaOrder->setFailureAttempts(0);
                    $edaOrder->setOrderType($orderType);
                    $this->edaOrdersResource->save($edaOrder);
                }
            } else {
                $data = [
                    'order_id' => $orderId,
                    'order_sent' => 0,
                    'order_type' => $orderType,
                    'failure_attempts' => 0
                ];
                $edaOrder = $this->edaOrdersFactory->create();
                $edaOrder->setData($data);
                $this->edaOrdersResource->save($edaOrder);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Update order to eda order table failed for orderId : '.$orderId.'-'.$e->getMessage()
            );
        }
    }

    /**
     * Return company name
     *
     * @param int $customerId
     * @return string|null
     */
    public function getCompanyName($customerId)
    {
        $companyName = '';
        $company = $this->companyManagement->getByCustomerId($customerId);
        if ($company) {
            $companyName = $company->getCompanyName();
        }
        return $companyName;
    }

    /**
     * Get Cart Price Rule Data
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getDiscountData($order)
    {
        $appliedRules = [];
        $discountType = 0;
        $appliedRuleIds = $order->getAppliedRuleIds();
        $discountTypeCode = '';
        try {
            if ($appliedRuleIds != '') {
                $appliedRuleIds = explode(',', $appliedRuleIds);
                foreach ($appliedRuleIds as $ruleId) {
                    $cartPriceRule = $this->rule->create()->load($ruleId);
                    $discountType = $cartPriceRule->getDiscountTypeRule();
                    $discountTypeCode = $cartPriceRule->getDiscountTypeCode();
                    if ($discountType == 4) {
                        $appliedRules[$ruleId]['data'] = $cartPriceRule->getData();
                        $dataToEncode = $cartPriceRule->getData('conditions_serialized');
                        $conditions = $this->serializerInterface->unserialize($dataToEncode);
                        foreach ($conditions['conditions'] as $condition) {
                            if (isset($condition['conditions'])) {
                                foreach ($condition['conditions'] as $conditionType) {
                                    if ($conditionType['type'] == \Magento\SalesRule\Model\Rule\Condition\Product::class
                                        && $conditionType['attribute'] == 'sku'
                                    ) {
                                        $appliedRules['sku_npi'][$conditionType['value']] = [
                                            'discount_type'=>$discountType,
                                            'discount_amount'=>$cartPriceRule->getDiscountAmount(),
                                            'sku' => $conditionType['value']
                                        ];
                                    }
                                }
                            }
                        }
                    } else {
                        $appliedRules['other_discounts'] = [
                            'discount_type' => $discountType,
                            'discount_amount' => $cartPriceRule->getDiscountAmount(),
                            'discount_qty' => $cartPriceRule->getDiscountQty()
                        ];
                        break;
                    }
                }
            }
            return [
                'discount_data'=>$appliedRules,
                'discount_type'=>$discountType,
                'discount_type_code' => $discountTypeCode
            ];
        } catch (\Exception $e) {
            $this->logger->info(
                'Discount exception order push to Eda Order Id '.$order->getId().' '.$e->getMessage()
            );
            return ['discount_data'=>[],'discount_type'=>'', 'discount_type_code' => ''];
        }
    }

    /**
     * Push Order Details to Eda
     *
     * @param OrderInterface $order
     * @param string $channel
     */
    public function pushOrderToEda($order, $channel)
    {
        $result = false;
        $logEnabled = $this->dataHelper->getSystemConfigValue(self::LOG_ENABLED_PATH);
        $apiEndpoint = $this->dataHelper->getSystemConfigValue(self::EDA_ORDER_ENDPOINT_PATH);
        try {
            $edaOrder = $this->dataHelper->getOrderForEdaUpdate($order->getEntityId(), $channel);
            if ($edaOrder != '' && $edaOrder->getOrderSent() != 1) {
                $orderData = json_encode($this->formatOrderData($order, $channel));
                if ($logEnabled) {
                    $this->dataHelper->logEdaOrderUpdateRequest(
                        '==============================================',
                    );
                    $this->dataHelper->logEdaOrderUpdateRequest('Request : OrderId - '.$order->getEntityId());
                    $this->dataHelper->logEdaOrderUpdateRequest($orderData);
                }
                $status = $this->integrationHelper->postDataToEda($orderData, $apiEndpoint);
                $statusDecoded = json_decode($status, true);
                if (isset($statusDecoded['success']) && $statusDecoded['success']) {
                    $edaOrder->setOrderSent(1);
                    $this->edaOrdersResource->save($edaOrder);
                    $result = true;
                } else {
                    $this->edaOrdersResource->save($edaOrder->setFailureAttempts($edaOrder->getFailureAttempts() + 1));
                }
                if ($logEnabled) {
                    $statusLog = ($result) ? 'success' : 'failure';
                    $this->dataHelper->logEdaOrderUpdateRequest('Response : '.$statusLog);
                    $this->dataHelper->logEdaOrderUpdateRequest($status);
                }
            }
        } catch (\Exception $e) {
            $result = false;
            $this->messageManager->addErrorMessage('Order Push Failed'.$e->getMessage());
            if ($logEnabled) {
                $this->dataHelper->logEdaOrderUpdateRequest(
                    'Exception occurred for Order Id : '.$order->getEntityId().' '.$e->getMessage()
                );
            }
        }
        return $result;
    }

    /**
     * Update Order to EDA
     *
     * @param $order
     * @param $channel
     */
    public function processOrderSendToEda($order, $channel)
    {
        $orderSent = $this->pushOrderToEda($order, $channel);
        if ($orderSent) {
            $order->addCommentToStatusHistory(
                __('Updated order to EDA for Channel : '.$channel)
            )->setIsCustomerNotified(false);
            $order->save();
        }
        return $orderSent;
    }
}
