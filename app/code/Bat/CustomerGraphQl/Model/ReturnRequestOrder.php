<?php

namespace Bat\CustomerGraphQl\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Bat\Sales\Helper\Data;
use Bat\Rma\Helper\Data as RmaHelper;

class ReturnRequestOrder
{

  /**
   * Order type
   */
    public const ORDER_TYPE = "IRO";
  /**
   * @var StoreManagerInterface
   */
    protected $storeManager;

   /**
    * @var CartManagementInterface
    */
    protected $quoteManagement;

   /**
    * @var CustomerRepositoryInterface
    */
    protected $customerRepository;

   /**
    * @var QuoteFactory
    */
    protected $quoteFactory;

   /**
    * @var ProductRepositoryInterface
    */
    protected $productRepository;

   /**
    * @var AddressRepositoryInterface
    */
    protected $addressRepository;

   /**
    * @var Data
    */
    protected $orderHelper;

    /**
     * @var RmaHelper
     */
    private RmaHelper $rmaHelper;

    /**
     * ReturnRequestOrder constructor
     *
     * @param StoreManagerInterface $storeManager
     * @param CartManagementInterface $cartManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteFactory $quoteFactory
     * @param ProductRepositoryInterface $productRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param Data $orderHelper
     * @param RmaHelper $rmaHelper
     */

    public function __construct(
        StoreManagerInterface $storeManager,
        CartManagementInterface $cartManagement,
        CustomerRepositoryInterface $customerRepository,
        QuoteFactory $quoteFactory,
        ProductRepositoryInterface $productRepository,
        AddressRepositoryInterface $addressRepository,
        Data $orderHelper,
        RmaHelper $rmaHelper
    ) {
        $this->storeManager = $storeManager;
        $this->quoteManagement = $cartManagement;
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
        $this->productRepository = $productRepository;
        $this->addressRepository = $addressRepository;
        $this->orderHelper = $orderHelper;
        $this->rmaHelper = $rmaHelper;
    }

    /**
     * CreateOrder return order
     *
     * @param array $products
     * @param int $customerId
     */
    public function createOrder(array $products, int $customerId)
    {
        try {
             $customer = $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            return ['error' => 1, 'msg' => 'Undefined user by id: ' . $customerId];
        }
        $productIdsForStatusUpdate = [];
        try {
            $quote = $this->quoteFactory->create();
            $store = $this->storeManager->getStore();
            $quote->setStoreId($this->orderHelper->getDefaultStoreId());
            $quote->assignCustomer($customer);
            $adminStoreId = $this->orderHelper->getAdminStoreId();
            foreach ($products as $item) {
                $product = $this->productRepository->get($item['sku']);
                $product->setStoreId($adminStoreId);
                $productIdsForStatusUpdate[] = $product->getId();
                $stockData = $product->getStockData();
                $stockData['manage_stock'] = 0;
                $stockData['use_config_manage_stock'] = 0;
                $product->setStockData($stockData);
                $product = $this->productRepository->save($product);
                $quote->addProduct($product, $item['qty']);
            }
            $billingAddress = null;
            $shippingAddress = null;
            $billingAddress = $this->addressRepository->getById($customer->getDefaultBilling());
            $shippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());
            if ($billingAddress) {
                $quote->getBillingAddress()->importCustomerAddressData($billingAddress);
            }
            if ($shippingAddress) {
                $quote->getShippingAddress()->importCustomerAddressData($shippingAddress);
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setCollectShippingRates(true)
                    ->collectShippingRates()->setShippingMethod('flatrate_flatrate');
            }
            $quote->setEdaOrderType(self::ORDER_TYPE);
            $quote->setEdaOrderType(self::ORDER_TYPE);
            $quote->setPaymentMethod('banktransfer');
            $quote->setInventoryProcessed(false);
            $quote->setIsReturnOrder('1');
            $quote->save();
            $quote->getPayment()->importData(['method' => 'banktransfer']);
            $quote->collectTotals()->save();
            $order = $this->quoteManagement->submit($quote);
            $this->orderHelper->updateProduct($productIdsForStatusUpdate);
            $order->setOrderType(__('Return Order'));
            $order->setReturnSwiftCode('10');
            $order->setReturnSwiftReason('151');
            $order->addCommentToStatusHistory(
                __('Account Closure Return Order')
            )->setIsCustomerNotified(false);
            $order->save();
            try {
                $this->orderHelper->updateOrderToEda(
                    $order->getEntityId(),
                    self::ORDER_TYPE,
                    'OMS',
                    $order->getIncrementId()
                );
            } catch (Exception $e) {
                $this->logUpdateRequest($e->getMessage());
            }
            return ['success' => 1, 'msg' => 'Order was successfully placed, order number: ' .
                $order->getIncrementId(), 'order_id' => $order->getIncrementId(),
                'return_order_id' => $order->getEntityId()];
        } catch (\Exception $e) {
            $this->orderHelper->updateProduct($productIdsForStatusUpdate);
            $this->logUpdateRequest($e->getMessage());
            return ['error' => 1, 'msg' => $e->getMessage()];
        }
    }

  /**
   * Create Order In EDA Logs
   *
   * @param string $message
   * @throws Zend_Log_Exception
   */
    public function logUpdateRequest($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/accountClouserItemReturn.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
