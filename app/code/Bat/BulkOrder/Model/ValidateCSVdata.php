<?php

namespace Bat\BulkOrder\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\QuoteGraphQl\Helper\Data as PlaceOrderHelper;
use Bat\CustomerBalanceGraphQl\Helper\Data as CustomerBalanceHelper;
use Bat\CustomerBalance\Helper\Data as CustomerBalanceData;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ValidateCSVdata extends AbstractModel
{

    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var PlaceOrderHelper
     */
    private PlaceOrderHelper $placeOrderHelper;

    /**
     * @var CustomerBalanceHelper
     */
    private CustomerBalanceHelper $customerBalanceHelper;

    /**
     * @var CustomerBalanceData
     */
    private CustomerBalanceData $customerBalanceData;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    /**
     * @param CollectionFactory $customerCollectionFactory
     * @param ProductRepository $productRepository
     * @param Data $helper
     * @param StockRegistryInterface $stockRegistry
     * @param CustomerRepositoryInterface $customerRepository
     * @param PlaceOrderHelper $placeOrderHelper
     * @param CustomerBalanceHelper $customerBalanceHelper
     * @param CustomerBalanceData $customerBalanceData
     * @param GetSalableQuantityDataBySku $getSalableQtyDataBySku
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        CollectionFactory $customerCollectionFactory,
        ProductRepository $productRepository,
        Data $helper,
        StockRegistryInterface $stockRegistry,
        CustomerRepositoryInterface $customerRepository,
        PlaceOrderHelper $placeOrderHelper,
        CustomerBalanceHelper $customerBalanceHelper,
        CustomerBalanceData $customerBalanceData,
        GetSalableQuantityDataBySku $getSalableQtyDataBySku,
        OrderFactory $orderFactory
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->_productRepository = $productRepository;
        $this->helper = $helper;
        $this->stockRegistry = $stockRegistry;
        $this->customerRepository = $customerRepository;
        $this->placeOrderHelper =  $placeOrderHelper;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->customerBalanceData = $customerBalanceData;
        $this->getSalableQuantityDataBySku = $getSalableQtyDataBySku;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Validate Outlet Data
     *
     * @param array $orderDetails
     */
    public function execute($orderDetails)
    {
        $validateData = $validateData['message'] = [];
        $validateData['status'] = 'success';
        $priceTagInvalid = false;
        $productNotValidQty = false;
        $isValidate = '';
        foreach ($orderDetails as $orderData) {
            $notChild = false;
            $notValidQty = false;
            $outletId = $orderData['outlet_id'];
            $is_parent = $orderData['is_parent'];
            if (!$this->validCustomer($outletId)) {
                $validateData['message'][] = '{"outletid":"'.$outletId.'","sku":"","context":"not valid"}';
            } else {
                $parentOutletId = $orderData['parent_outlet_id'];
                $isParent = $this->isParentOutlet($outletId);
                if ($is_parent && (!$isParent)) {
                    $validateData['message'][] = '{"outletid":"'.$outletId.'","sku":"","context":"not Parent Outlet"}';
                }

                if (!$is_parent && (!$isParent)) {
                    $childOutletData = $this->getChildOutlet($parentOutletId);
                    if (!in_array($outletId, $childOutletData)) {
                        $validateData['message'][] =
                            '{"outletid":"'.$outletId.'","sku":"","context":"not Child Outlet"}';
                            $notChild = true;
                    }
                }
                if (!$notChild) {
                    $validOutlet = $this->isOutletIdValidCustomer($outletId);
                    if ($validOutlet != 'success') {
                        $validateData['message'][] = $validOutlet;
                    } else {
                        $items = $orderData['items'];
                        $quantity = 0;
                        $noSkus = [];
                        $skus = [];
                        foreach ($items as $item) {
                            try {
                                $product = $this->_productRepository->get($item['sku']);
                                $isInStock = $this->isSkuInStock($product);
                                if ($isInStock != 'In Stock') {
                                    $validateData['message'][] =
                                    '{"outletid":"'.$outletId.'","sku":"'.$item['sku'].'","context":"out of stock"}';
                                } else {
                                    if (!$this->getIsPriceTag($item['sku']) && !preg_match('/^([0-9]+)$/', $item['quantity'])) {
                                        $productNotValidQty = true;
                                        $notValidQty = true;

                                    } elseif ((!$this->getIsPriceTag($item['sku'])) && ($item['quantity'] % 10 != 0)) {
                                        $validateData['message'][] =
                                        '{"outletid":"'.$outletId.'","sku":"'
                                        .$item['sku']
                                        .'","context":"Allowed multiple of 10"}';
                                    } elseif ($this->getIsPriceTag($item['sku']) && ($item['quantity'] > 1) || !preg_match('/^([0-9]+)$/', $item['quantity'])) {
                                        $priceTagInvalid = true;
                                    } else {
                                        if (!$this->getIsPriceTag($item['sku']) && preg_match('/^([0-9]+)$/', $item['quantity'])) {
                                            $quantity += $item['quantity'];
                                        }
                                    }
                                }
                                $skus[] = $item['sku'];
                            } catch (NoSuchEntityException) {
                                $validateData['message'][] =
                                '{"outletid":"'.$outletId.'","sku":"'.$item['sku'].'","context":"sku not valid"}';
                                $noSkus[] = $item['sku'];
                            }
                        }
                        if (count($noSkus) > 0) {
                            $isValidate = 'success';
                        }
                        if (count($skus) > 0 && !$notValidQty && $quantity > 0) {
                            $isValidate = $this->validateQuantity($quantity, $outletId);
                            if ($isValidate != 'success') {
                                $validateData['message'][] = $isValidate;
                            }
                        }

                        $getOrderFrequencyStatus = $this->getOrderFrequencyData($outletId);

                        if ($getOrderFrequencyStatus != '') {
                            $validateData['message'][] = $getOrderFrequencyStatus;
                        }

                        $failedOrders = $this->getCustomerFailedOrders($outletId);
                        if ($failedOrders != '') {
                            $validateData['message'][] = $failedOrders;
                        }

                        $getPaymentOverDue = $this->getOverDueData($outletId);

                        if ($getPaymentOverDue != '') {
                            $validateData['message'][] = $getPaymentOverDue;
                        }

                    }
                }
            }
        }
        if ($productNotValidQty) {
            $validateData['message'][] =
                '{"outletid":"'.' '.'","sku":"'
                .' '.'","context":"Quantity is not valid"}';
        }
        if ($priceTagInvalid) {
            $validateData['message'][] =
                '{"outletid":"'.' '.'","sku":"'
                .' '.'","context":"Price Tag item for allowed quantity 1"}';
        }
        if (array_key_exists('message', $validateData) && count($validateData['message']) > 0) {
            $validateData['message'] = array_unique($validateData['message']);
            $validateData['status'] = 'failed';
        }
        return $validateData;
    }

     /**
      * Validate if outletid is already registered or not.
      *
      * @param string $outletId
      * @return string
      */
    public function isOutletIdValidCustomer($outletId)
    {
        $customerStatus = 'success';
        $collection = $this->getCustomer('outlet_id', $outletId);
        if ($collection->getSize() > 0) {
            $customer = $collection->getFirstItem();
            $customerDetatils = $this->customerRepository->getById($customer->getId());
            $approvalStatus = '';
            if (!empty($customerDetatils->getCustomAttribute('approval_status'))) {
                $approvalStatus = $customerDetatils->getCustomAttribute('approval_status')->getValue();
            }

            if ($approvalStatus != 1) {
                $customerStatus = '{"outletid":"'.$outletId.'","sku":"","context":"not approved"}';
            }

            if ($approvalStatus == 6
                || $approvalStatus == 8
                || $approvalStatus == 10
                || $approvalStatus == 11
                || $approvalStatus == 14) {
                $customerStatus = '{"outletid":"'.$outletId.'","sku":"","context":"closure under review"}';
            }

            if ($approvalStatus == 12 || $approvalStatus == 13) {
                $customerStatus = '{"outletid":"'.$outletId.'","sku":"","context":"Address change under review"}';
            }

            if ($approvalStatus == 4) {
                $customerStatus = '{"outletid":"'.$outletId.'","sku":"","context":"VBA change under review"}';
            }

            if ($approvalStatus == 7 || $approvalStatus == 9) {
                $customerStatus = '{"outletid":"'.$outletId.'","sku":"","context":"closed"}';
            }

        }
        return $customerStatus;
    }

    /**
     * Valid Customer
     *
     * @param string $outletId
     * @return boolean
     */
    public function validCustomer($outletId)
    {
        $collection = $this->getCustomer('outlet_id', $outletId);
        if ($collection->getSize() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Validate if outletid is parent or not.
     *
     * @param string $outletId
     * @return boolean
     */
    public function isParentOutlet($outletId)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_id', $outletId);

        $customer = $collection->getFirstItem();
        $customerDetatils = $this->customerRepository->getById($customer->getId());
        $parentId = '';
        if (!empty($customerDetatils->getCustomAttribute('is_parent'))) {
            $parentId = $customerDetatils->getCustomAttribute('is_parent')->getValue();
        }

        return ($parentId == 1) ? true : false;
    }

    /**
     * Get Parent outlet Id.
     *
     * @param string $outletId
     * @return string
     */
    public function getParentOutlet($outletId)
    {
        $customer = '';
        $collection = $this->getCustomer('outlet_id', $outletId);

        $customer = $collection->getFirstItem();
        $parentId = $customer->getParentOutletId();

        return $parentId;
    }

    /**
     * Get Child outlets.
     *
     * @param string $parentOutletId
     * @return array
     */
    public function getChildOutlet($parentOutletId)
    {
        $childOutlet = [];
        $collection = $this->getCustomer('parent_outlet_id', $parentOutletId);

        if ($collection->getSize() > 0) {
            foreach ($collection as $data) {
                    $childOutlet[] = $data->getOutletId();
            }
        }
        return $childOutlet;
    }

    /**
     * Getting customer collection.
     *
     * @param string $field
     * @param string $value
     * @return array
     */
    public function getCustomer($field, $value)
    {
        return $this->customerCollectionFactory->create()
                   ->addAttributeToFilter($field, $value);
    }

    /**
     * Validate if sku is valid or not.
     *
     * @param string $sku
     * @return boolean
     */
    public function isSkuExist($sku)
    {

        try {
            $productData = $this->_productRepository->get($sku);
            return true;
        } catch (\Exception $e) {
              return false;
        }
    }

   /**
    * Validate if sku is in stock or not.
    *
    * @param object $product
    * @return boolean
    */
    public function isSkuInStock($product)
    {
        $stockStatus = $this->getStockStatus($product->getId());
        $saleableQty = 1;
        $salableQtyData = $this->getSalableQuantityDataBySku->execute($product->getSku());
        if (isset($salableQtyData[0])) {
            if ($salableQtyData[0]['manage_stock'] == 1) {
                $saleableQty = $salableQtyData[0]['qty'];
            } elseif (empty($salableQtyData[0]['manage_stock'])) {
                $saleableQty = 1;
            }
        }

        return (($stockStatus) && ($saleableQty != 0)) ? __('In Stock') : __('Out of Stock');
    }

    /**
     * Validate if sku is price tag or not.
     *
     * @param string $sku
     * @return boolean
     */
    public function getIsPriceTag($sku)
    {
        $productData = $this->_productRepository->get($sku);
        $priceTag = $productData->getPricetagType();
        return ($priceTag) ? 1 : 0;
    }

    /**
     * Validate if quantity is valid or not.
     *
     * @param string $quantity
     * @param string $outletId
     * @return string
     */
    public function validateQuantity($quantity, $outletId)
    {
        $validateText = 'success';
        if ($quantity < $this->helper->getMinimumCartQty()) {
            $validateText = '{"outletid":"'.$outletId.'","sku":"","context":"Minimum quantity required"}';
        }
        if ($this->helper->getMaximumCartQty() < $quantity) {
            $validateText = '{"outletid":"'.$outletId.'","sku":"","context":"Maximum quantity allowed"}';
        }
        return $validateText;
    }

    /**
     * Validate if outlet is valid for placing order.
     *
     * @param string $outletId
     * @return string
     */
    public function getOrderFrequencyData($outletId)
    {
        $orderFrequencyMessage = '';
        $collection = $this->getCustomer('outlet_id', $outletId);
        $customer = $collection->getFirstItem();
        $customerDetatils = $this->customerRepository->getById($customer->getId());
        $orderFrequency = $this->placeOrderHelper->canPlaceOrder($customerDetatils);
        if (!$orderFrequency) {
            $orderFrequencyMessage = '{"outletid":"'.$outletId.'","sku":"","context":"Order frequency exceeded"}';
        }
        return $orderFrequencyMessage;
    }

    /**
     * Validate if outlet is having failed orders.
     *
     * @param string $outletId
     * @return string
     */
    public function getCustomerFailedOrders($outletId)
    {
        $collection = $this->getCustomer('outlet_id', $outletId);
        $customer = $collection->getFirstItem();

        $unpaidTotal = $this->customerBalanceData->getUnpaidOrdersTotal($customer->getId());
        $unConfirmedTotal = $this->customerBalanceData->getUnconfirmedOrdersTotal($customer->getId());
        $zlobOrders = $this->customerBalanceData->getZlobOrders($customer->getId());

        $failedOrderMessage = '';
        if ($unpaidTotal > 0 || $unConfirmedTotal > 0 || $zlobOrders == 1) {
            $failedOrderMessage = '{"outletid":"'.$outletId.'","sku":"","context":"Unconfirmed/Unpaid Orders"}';
        }
        return $failedOrderMessage;
    }

    /**
     * Validate if outlet is valid for placing order.
     *
     * @param string $outletId
     * @return string
     */
    public function getOverDueData($outletId)
    {

        $collection = $this->getCustomer('outlet_id', $outletId);
        $customer = $collection->getFirstItem();
        $customerDetatils = $this->customerRepository->getById($customer->getId());

        $isCreditCustomer = false;
        if (!empty($customerDetatils->getCustomAttribute('is_credit_customer'))) {
            $isCreditCustomer = $customerDetatils->getCustomAttribute('is_credit_customer')->getValue();
        }

        if ($isCreditCustomer) {
            $totalArLimit = $this->getTotalArLimit($customer);
            $creditDue = $this->getCreditCustomerDue($customer->getId(), $totalArLimit);
            if ($creditDue) {
                return '{"outletid":"'.$outletId.'","sku":"","context":"Overdue Payment"}';

            }
        } else {
            if ($this->placeOrderHelper->checkPaymentOverDue($customer->getId())) {
                return '{"outletid":"'.$outletId.'","sku":"","context":"Overdue Payment"}';
            }
        }

        return '';
    }

    /**
     * Get Stock status
     *
     * @param int $productId
     * @return bool|int
     * return stock status of a product
     */
    public function getStockStatus($productId)
    {
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $isInStock = $stockItem ? $stockItem->getIsInStock() : false;
        return $isInStock;
    }

    /**
     * Return Total AR Limit
     *
     * @param string $customer
     * @return int
     */
    public function getTotalArLimit($customer)
    {
        $totalARLimit = 0;
        if ($customer->getCustomAttribute('total_ar_limit') !='') {
            $totalARLimit = $customer->getCustomAttribute('total_ar_limit')->getValue();
        }
        return $totalARLimit;
    }

    /**
     * Get Credit Customer Remaining Due
     *
     * @param int $customerId
     * @param float|int $totalArLimit
     * @return string
     */
    public function getCreditCustomerDue($customerId, $totalArLimit)
    {
        $totalDue = 0;
        $order = $this->customerBalanceHelper->getCustomerOrder($customerId);
        foreach ($order as $orderItem) {
            $totalDue = $totalDue + $orderItem['total_due'];
        }

        if ($totalDue > $totalArLimit) {
            return true;
        }

        $customer = $this->customerRepository->getById($customerId);
        if($this->customerBalanceHelper->getCustomerStoreCreditIsOverdueStatus($customer)){
            return true;
        }
        return false;
    }
}
