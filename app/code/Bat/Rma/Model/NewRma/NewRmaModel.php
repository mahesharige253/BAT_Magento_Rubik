<?php

namespace Bat\Rma\Model\NewRma;

use Bat\Sales\Model\EdaOrderType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Bat\Sales\Helper\Data as SalesHelper;
use Bat\Rma\Helper\Data as RmaHelper;

/**
 * @class NewRmaModel
 * Create RMA/Orders Model
 */
class NewRmaModel
{
    public const SWIFTCODEACTION = [
        '10' => ['001'=>'1','201'=>'1','151'=>'1'],
        '11' => ['001'=>'0','201'=>'0','151'=>'1'],
        '12' => ['001'=>'0','201'=>'0','151'=>'1'],
        '13' => ['001'=>'1','201'=>'0','151'=>'0'],
        '14' => ['001'=>'1','201'=>'0','151'=>'0'],
        '15' => ['001'=>'1','201'=>'1','151'=>'1'],
        '16' => ['001'=>'1','201'=>'0','151'=>'0'],
        '17' => ['001'=>'1','201'=>'0','151'=>'0'],
        '18' => ['001'=>'1','201'=>'0','151'=>'0'],
        '19' => ['001'=>'1','201'=>'0','151'=>'0'],
        '20' => ['001'=>'0','201'=>'1','151'=>'0'],
        '21' => ['001'=>'0','201'=>'0','151'=>'1'],
    ];
    private CustomerRepositoryInterface $customerRepository;
    private OrderRepositoryInterface $orderRepository;
    private ProductRepositoryInterface $productRepository;
    private AddressRepositoryInterface $addressRepository;
    private OrderManagementInterface $orderManagement;
    private QuoteManagement $quoteManagement;
    private StoreManagerInterface $storeManager;
    private QuoteFactory $quote;
    private SalesHelper $salesHelper;
    private RmaHelper $rmaHelper;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param OrderManagementInterface $orderManagement
     * @param QuoteManagement $quoteManagement
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quote
     * @param SalesHelper $salesHelper
     * @param RmaHelper $rmaHelper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository,
        AddressRepositoryInterface $addressRepository,
        OrderManagementInterface $orderManagement,
        QuoteManagement $quoteManagement,
        StoreManagerInterface $storeManager,
        QuoteFactory $quote,
        SalesHelper $salesHelper,
        RmaHelper $rmaHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->addressRepository = $addressRepository;
        $this->orderManagement = $orderManagement;
        $this->quoteManagement = $quoteManagement;
        $this->storeManager = $storeManager;
        $this->quote = $quote;
        $this->salesHelper = $salesHelper;
        $this->rmaHelper = $rmaHelper;
    }

    /**
     * Create IRO order
     *
     * @param CustomerInterface $customer
     * @param array $returnProducts
     * @param string $returnReason
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createIroOrder($customer, $returnProducts, $returnReason)
    {
        $productIdsForStatusUpdate = [];
        $orderCreated = ['success' => false,'order_id' => '', 'message' => ''];
        try {
            $orderReason = $this->getOrderReason($returnReason);
            $customerId = $customer->getId();
            $storeId = $customer->getStoreId();
            $quote = $this->quote->create();
            $quote->setStoreId($this->salesHelper->getDefaultStoreId());
            $quote->assignCustomer($customer);
            $adminStoreId = $this->salesHelper->getAdminStoreId();
            foreach ($returnProducts as $productId => $qtyRequested) {
                $product = $this->productRepository->getById($productId);
                $product->setStoreId($adminStoreId);
                $productIdsForStatusUpdate[] = $productId;
                $stockData = $product->getStockData();
                $stockData['manage_stock'] = 0;
                $stockData['use_config_manage_stock'] = 0;
                $product->setStockData($stockData);
                $product = $this->productRepository->save($product);
                $quote->addProduct($product, $qtyRequested);
            }
            $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
            $shippingAddress = $quote->getShippingAddress()->setCustomerAddressId($shippingAddress->getId());
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod('flatrate_flatrate');
            $quote->setInventoryProcessed(true);
            $quote->setUseCustomerBalance(false);
            $quote->setEdaOrderType(EdaOrderType::IRO);
            $quote->setIsReturnOrder(1);
            $quote->collectTotals()->save();
            $quote->setPaymentMethod('banktransfer');
            $quote->getPayment()->importData(['method' => 'banktransfer']);
            $order = $this->quoteManagement->submit($quote);
            $this->salesHelper->updateProduct($productIdsForStatusUpdate);
            $order->setTotalPaid($order->getTotalDue());
            $order->setTotalDue(0);
            $order->setBaseTotalDue(0);
            $order->setOrderType('Return Order');
            $order->setReturnSwiftCode($returnReason);
            $order->setReturnSwiftReason($orderReason);
            $order = $this->orderRepository->save($order);
            $orderCreated['success'] = true;
            $orderCreated['order_id'] = $order->getIncrementId();
            $orderCreated['return_order_id'] = $order->getEntityId();
        } catch (\Exception $e) {
            $this->salesHelper->updateProduct($productIdsForStatusUpdate);
            $orderCreated['success'] = false;
            $orderCreated['message'] =  $e->getMessage();
        }
        return $orderCreated;
    }

    /**
     * Get Order Reason code
     *
     * @param string $returnReason
     * @return int|string
     */
    public function getOrderReason($returnReason)
    {
        $orderReason = '';
        if ($returnReason == 10 || $returnReason == 15) {
            $orderReason = "151";
        } else {
            foreach (self::SWIFTCODEACTION[$returnReason] as $orderReasonCode => $value) {
                if ($value) {
                    $orderReason = $orderReasonCode;
                    break;
                }
            }
        }
        return $orderReason;
    }
}
