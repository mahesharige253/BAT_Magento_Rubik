<?php

namespace Bat\ShipmentUpdate\Model\Api;

use Bat\Customer\Helper\Data as CustomerHelperData;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Sales\Helper\Data;
use Bat\Rma\Helper\Data as RmaHelper;
use Bat\Sales\Model\EdaOrderType;
use Bat\Sales\Model\SendOrderDetails;
use Bat\ShipmentUpdate\Api\OrderGoodsReceivedInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\ItemFactory;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\RmaFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\Order;
use Bat\Sales\Model\BatOrderStatus;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Bat\Rma\Model\NewRma\ReturnOrderModel;

/**
 * @class GoodsReceived
 * Check Order  delivery status
 */
class GoodsReceived implements OrderGoodsReceivedInterface
{
    /**
     * @var Order
     */
    private Order $order;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var EventManager
     */
    private EventManager $eventManager;

    /**
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var CustomerHelperData
     */
    private CustomerHelperData $helperData;

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var AddressRepositoryInterface
     */
    private AddressRepositoryInterface $addressRepository;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var OrderManagementInterface
     */
    private OrderManagementInterface $orderManagement;

    /**
     * @var QuoteFactory
     */
    private QuoteFactory $quote;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var ItemFactory
     */
    private ItemFactory $rmaItemFactory;

    /**
     * @var RmaFactory
     */
    private RmaFactory $rmaFactory;

    /**
     * @var DateTimeFactory
     */
    private DateTimeFactory $dateTimeFactory;

    /**
     * @var RmaRepositoryInterface
     */
    private RmaRepositoryInterface $rmaRepository;

    /**
     * @var ReturnOrderModel
     */
    private ReturnOrderModel $returnOrderModel;

    /**
     * @var RmaHelper
     */
    private RmaHelper $rmaHelper;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @param Order $order
     * @param OrderRepositoryInterface $orderRepository
     * @param EventManager $eventManager
     * @param Data $dataHelper
     * @param KakaoSms $kakaoSms
     * @param CustomerHelperData $helperData
     * @param CompanyManagementInterface $companyManagement
     * @param QuoteFactory $quote
     * @param ProductRepositoryInterface $productRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param OrderManagementInterface $orderManagement
     * @param QuoteManagement $quoteManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param RmaFactory $rmaFactory
     * @param ItemFactory $rmaItemFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param RmaRepositoryInterface $rmaRepository
     * @param ReturnOrderModel $returnOrderModel
     * @param RmaHelper $rmaHelper
     * @param SendOrderDetails $sendOrderDetails
     */
    public function __construct(
        Order $order,
        OrderRepositoryInterface $orderRepository,
        EventManager $eventManager,
        Data $dataHelper,
        KakaoSms $kakaoSms,
        CustomerHelperData $helperData,
        CompanyManagementInterface $companyManagement,
        QuoteFactory $quote,
        ProductRepositoryInterface $productRepository,
        AddressRepositoryInterface $addressRepository,
        OrderManagementInterface $orderManagement,
        QuoteManagement $quoteManagement,
        CustomerRepositoryInterface $customerRepository,
        RmaFactory $rmaFactory,
        ItemFactory $rmaItemFactory,
        DateTimeFactory $dateTimeFactory,
        RmaRepositoryInterface $rmaRepository,
        ReturnOrderModel $returnOrderModel,
        RmaHelper $rmaHelper,
        SendOrderDetails $sendOrderDetails
    ) {
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->dataHelper = $dataHelper;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->companyManagement = $companyManagement;
        $this->quote = $quote;
        $this->productRepository = $productRepository;
        $this->addressRepository = $addressRepository;
        $this->orderManagement = $orderManagement;
        $this->quoteManagement = $quoteManagement;
        $this->customerRepository = $customerRepository;
        $this->rmaFactory = $rmaFactory;
        $this->rmaItemFactory = $rmaItemFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->rmaRepository = $rmaRepository;
        $this->returnOrderModel = $returnOrderModel;
        $this->rmaHelper = $rmaHelper;
        $this->sendOrderDetails = $sendOrderDetails;
    }

    /**
     * Update delivery status
     *
     * @param mixed $data
     * @return array[]
     */
    public function goodsReceived($data)
    {
        $result = ['success' => false, 'message' => ''];
        try {
            $this->addLog("====================== Goods Received ==============================");
            $this->addLog("Request : ");
            $this->addLog(json_encode($data));
            $this->addLog("Response : ");
            $orderData = $this->order->loadByIncrementId($data['increment_id']);
            if (!$orderData->getId()) {
                throw new LocalizedException(__('Order not found'));
            }

            $order = $this->orderRepository->get($orderData->getId());
            if ($order->getGrConfirmed()) {
                throw new LocalizedException(__('GR status updated already'));
            }
            $edaOrderType = $order->getEdaOrderType();
            $allowedOrderTypes = ['ZLOB','IRO'];
            if (!in_array($edaOrderType, $allowedOrderTypes)) {
                throw new LocalizedException(__('Request allowed only for IRO and ZLOB orders'));
            }

            $orderItems = $order->getAllItems();
            $allItemsInOrder = [];
            foreach ($orderItems as $orderItem) {
                $sku = $orderItem->getSku();
                $qty = (int)$orderItem->getQtyOrdered();
                $allItemsInOrder[$sku] = [
                    'qty' => $qty
                ];
            }
            if (isset($data['batch_id'])) {
                $order->setBatchId($data['batch_id']);
            }
            $requestedItemSkuQty = [];
            $orderReasonStatus = [];
            $availableOrderReasons = ['fresh','damage','old'];
            $uomConversion = [];
            foreach ($data['items'] as $requestedItem) {
                if (isset($requestedItem['sku']) && $requestedItem['sku'] != '') {
                    try {
                        $product = $this->productRepository->get($requestedItem['sku']);
                        if ($product->getStatus() == 2) {
                            throw new LocalizedException(__('Product is Disabled'));
                        }
                        if ($edaOrderType == 'IRO' && $product->getPricetagType() != 0) {
                            throw new LocalizedException(__('Price Tags cannot be added for returns'));
                        }
                        $uom = (float)$product->getCustomAttribute('base_to_secondary_uom')->getValue();
                        $uomConversion[$requestedItem['sku']] = ($uom > 0) ? $uom : 1;
                    } catch (\Exception $e) {
                        $result['success'] = false;
                        $result['message'] = $requestedItem['sku'].' : '.$e->getMessage();
                        $this->addLog(json_encode($result));
                        return [$result];
                    }
                } else {
                    $result['success'] = false;
                    $result['message'] = 'sku is required on item level and cannot be empty';
                    $this->addLog(json_encode($result));
                    return [$result];
                }
                if (!isset($requestedItem['qty_requested']) ||
                    !is_numeric($requestedItem['qty_requested']) ||
                    $requestedItem['qty_requested'] <= 0) {
                    $result['success'] = false;
                    $result['message'] = $requestedItem['sku'].
                        ' : qty_requested is required on item level and cannot be non numeric and zero';
                    $this->addLog(json_encode($result));
                    return [$result];
                }
                if ($edaOrderType == 'IRO') {
                    if (!isset($requestedItem['status'])) {
                        $result['success'] = false;
                        $result['message'] = 'status is required on item level';
                        $this->addLog(json_encode($result));
                        return [$result];
                    } else {
                        if (!in_array($requestedItem['status'], $availableOrderReasons)) {
                            $result['success'] = false;
                            $result['message'] = 'Allowed values for order reason is '
                                .implode(', ', $availableOrderReasons);
                            $this->addLog(json_encode($result));
                            return [$result];
                        }
                    }
                    $orderReasonStatus[$requestedItem['sku']] = $requestedItem['status'];
                }
                $requestedItemSkuQty[$requestedItem['sku']] = $requestedItem['qty_requested'];
            }
            $grRequestedSkus = [];
            if ($edaOrderType == 'ZLOB') {
                $additionalSkuPassedErrors = [];
                $orderQtyMismatchedErrors = [];
                $skuMissedErrors = [];
                foreach ($requestedItemSkuQty as $grRequestSku => $grRequestQty) {
                    $grRequestedSkus[] = $grRequestSku;
                    if (!isset($allItemsInOrder[$grRequestSku])) {
                        $additionalSkuPassedErrors[] = $grRequestSku;
                    } else {
                        $requestOrderItemDetails = $allItemsInOrder[$grRequestSku];
                        $orderedQty = $requestOrderItemDetails['qty'];
                        $conversion = $uomConversion[$grRequestSku];
                        $requestedGrQty = ($grRequestQty*1000)/$conversion;
                        if ($orderedQty != $requestedGrQty) {
                            $orderQtyMismatchedErrors[] = $grRequestSku;
                        }
                    }
                }
                foreach ($allItemsInOrder as $orderSku => $ordererdQty) {
                    if (!in_array($orderSku, $grRequestedSkus)) {
                        $skuMissedErrors[] = $orderSku;
                    }
                }
                $errorMsgZlob = '';
                $result = ['success' => true];
                if (!empty($additionalSkuPassedErrors)) {
                    $result['success'] = false;
                    $errorMsgZlob = 'SKU Not part of original order items : '
                        .implode(',', $additionalSkuPassedErrors);
                }
                if (!empty($orderQtyMismatchedErrors)) {
                    $result['success'] = false;
                    $qtyErrorMessage = 'Requested quantity not matched with ordered quantity '
                        .implode(',', $orderQtyMismatchedErrors);
                    $errorMsgZlob = ($errorMsgZlob != '') ? $errorMsgZlob.', '.$qtyErrorMessage : $qtyErrorMessage;
                }
                if (!empty($skuMissedErrors)) {
                    $result['success'] = false;
                    $skuErrorMessage = 'SKU required for confirmation : '
                        .implode(',', $skuMissedErrors);
                    $errorMsgZlob = ($errorMsgZlob != '') ? $errorMsgZlob.', '.$skuErrorMessage : $skuErrorMessage;
                }
                if (!$result['success']) {
                    $result['message'] = $errorMsgZlob;
                    $this->addLog(json_encode($result));
                    return [$result];
                }
                $order->setGrConfirmed(1);
                $order->addCommentToStatusHistory(
                    __('GR Confirmation received: ')
                )->setIsCustomerNotified(false);
                $order = $order->save();
                $this->dataHelper->updateOrderToEda(
                    $order->getEntityId(),
                    $order->getEdaOrderType(),
                    'SWIFTPLUS',
                    $order->getIncrementId()
                );
                $this->sendOrderDetails->processOrderSendToEda($order, 'SWIFTPLUS');
                $result['success'] = true;
                $result['message'] = 'Request confirmed successfully';
                $this->addLog(json_encode($result));
                return [$result];
            } else {
                $newOrderItems = $this->removeDuplicateItems($order, $data['items'], $uomConversion);
                if ($newOrderItems['has_errors']) {
                    $result['success'] = false;
                    throw new LocalizedException(__($newOrderItems['message']));
                } else {
                    $order->setGrConfirmed(1);
                    $this->updateZreOrderData($order, $newOrderItems['items']);
                }
                $result['success'] = true;
                $result['message'] = 'Return status updated successfully';
            }
        } catch (LocalizedException $e) {
            $result['message'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->addLog($e->getMessage());
            $result['message'] = 'Something went wrong';
        }
        $this->addLog(json_encode($result));
        return [$result];
    }

    /**
     * Goods Received Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addlog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaGoodsReceived.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * update zre order data in custom table for creating zre orders
     *
     * @param OrderInterface $order
     * @param array $items
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function updateZreOrderData($order, $items,)
    {
        $returnOrderData = [];
        foreach ($items as $sku => $itemData) {
            $returnOrderItemData = [];
            $returnOrderItemData['fresh'] = $itemData['fresh'];
            $returnOrderItemData['old'] = $itemData['old'];
            $returnOrderItemData['damage'] = $itemData['damage'];
            $returnOrderItemData['qty'] = $itemData['qty'];
            $returnOrderItemData['sku'] = $itemData['sku'];
            $returnOrderData[] = $returnOrderItemData;
        }
        $this->addlog('Rma Final Data for ZRE orders: '.json_encode($returnOrderData));
        $this->rmaHelper->updateRmaForZreOrderCreate(
            [
                'order_id' => $order->getEntityId(),
                'customer_id' => $order->getCustomerId(),
                'rma_data' => json_encode($returnOrderData)
            ]
        );
        $order->addCommentToStatusHistory(
            __('GR Confirmation Received')
        )->setIsCustomerNotified(false);
        $order->save();
    }

    /**
     * Remove duplicate order line items and merge
     *
     * @param OrderInterface $order
     * @param mixed $requestItems
     */
    public function removeDuplicateItems($order, $requestItems, $uomConversion)
    {
        $skuTotalQty = [];
        $finalItems = [];
        $result = ['has_errors'=>false,'items' => [],'message'=>''];
        foreach ($requestItems as $item) {
            $requestQty = $item['qty_requested'];
            $sku = $item['sku'];
            $requestQty = ($requestQty*1000)/$uomConversion[$item['sku']];
            if ($requestQty != round($requestQty) || $requestQty <= 0) {
                $result['has_errors'] = true;
                $result['message'] = 'Request SKU '.$item['sku'].' quantity is not valid';
                return $result;
            }
            if (isset($skuTotalQty[$item['sku']])) {
                $skuTotalQty[$item['sku']] = $skuTotalQty[$item['sku']] + $requestQty;
            } else {
                $skuTotalQty[$item['sku']] = $requestQty;
            }
            if (isset($finalItems[$item['sku']])) {
                $finalItems[$item['sku']]['old'] = ($item['status'] == 'old') ?
                    $finalItems[$item['sku']]['old'] + $requestQty :  $finalItems[$item['sku']]['old'];

                $finalItems[$item['sku']]['fresh'] = ($item['status'] == 'fresh') ?
                    $finalItems[$item['sku']]['fresh'] + $requestQty : $finalItems[$item['sku']]['fresh'];

                $finalItems[$item['sku']]['damage'] = ($item['status'] == 'damage') ?
                    $finalItems[$item['sku']]['damage'] + $requestQty : $finalItems[$item['sku']]['damage'];
                $finalItems[$item['sku']]['qty'] = $finalItems[$item['sku']]['qty'] + $requestQty;
            } else {
                $data = [
                    'sku' => $item['sku'],
                    'qty' => $requestQty,
                    'fresh' => ($item['status'] == 'fresh') ? $requestQty : 0,
                    'old' => ($item['status'] == 'old') ? $requestQty : 0,
                    'damage' => ($item['status'] == 'damage') ? $requestQty : 0
                ];
                $finalItems[$item['sku']] = $data;
            }
        }
        $result['items'] = $finalItems;
        return $result;
    }
}
