<?php
namespace Bat\RequisitionList\Model;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Bat\RequisitionList\Helper\Data;
use Bat\RequisitionList\Model\Resolver\RequisitionList\GetAdminRequisitionList;
use Bat\GetCartGraphQl\Helper\Data as QuantityHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class NormalSeasonalOtherRlItems
{

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var GetAdminRequisitionList
     */
    private $getAdminRequisitionList;

    /**
     * @var QuantityHelper
     */
     protected $quantityHelper;

     /**
      * @var RequisitionListAdminFactory
      */
     protected $requisitionList;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param CollectionFactory $orderCollectionFactory
     * @param TimezoneInterface $timezoneInterface
     * @param Data $helper
     * @param GetAdminRequisitionList $getAdminRequisitionList
     * @param QuantityHelper $quantityHelper
     * @param RequisitionListAdminFactory $requisitionList
     * @param ProductRepositoryInterface $productRepository
     */

    public function __construct(
        CollectionFactory $orderCollectionFactory,
        TimezoneInterface $timezoneInterface,
        Data $helper,
        GetAdminRequisitionList $getAdminRequisitionList,
        QuantityHelper $quantityHelper,
        RequisitionListAdminFactory $requisitionList,
        ProductRepositoryInterface $productRepository
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->helper = $helper;
        $this->getAdminRequisitionList = $getAdminRequisitionList;
        $this->quantityHelper = $quantityHelper;
        $this->requisitionList = $requisitionList;
        $this->productRepository = $productRepository;
    }

    /**
     * Get Order History
     *
     * @param int $customerId
     * @return array
     */
    public function getOrderHistory($customerId)
    {
        $lastNumberOfMonth = $this->helper->getLastNumberOfMonths();
        $fromDate = date('Y-m-d H:i:s', strtotime('-'.$lastNumberOfMonth.' month', time()));
        $currentDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $finalstart = $this->timezoneInterface->convertConfigTimeToUtc($fromDate);
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', ['gteq' => $finalstart])
            ->addFieldToFilter('created_at', ['lteq' => $currentDateTime])
            ->addFieldToFilter('eda_order_type', 'ZOR')
            ->addFieldToFilter('status', 'complete');
        $numberOfOrder = $orderCollection->getSize();
        $productName = [];
        $productSkus = [];
        $qty = 0;
        $totalQty = 0;
        foreach ($orderCollection as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                try {
                    $product = $this->productRepository->get($item->getData('sku'));
                    if ($product->getIsPlp() && !$item->getData('is_price_tag')) {
                        $totalQty += $item->getData('qty_ordered');
                        if (array_key_exists($item->getData('sku'), $productSkus)) {
                            $qty = $productSkus[$item->getData('sku')];
                            $productSkus[$item->getData('sku')] = $qty + $item->getData('qty_ordered');
                        } else {
                            $productSkus[$item->getData('sku')] = $item->getData('qty_ordered');
                            $productName[] = $product->getName();
                        }
                    }
                } catch (NoSuchEntityException $e) {
                }
            }
        }
        $orderData = ['total_order_qty' => $totalQty,
                        'total_order' => $numberOfOrder,
                        'ordered_skus' => $productSkus,
                        'product_name' => $productName];
        return $orderData;
    }

    /**
     * Is Allowed Normal Seasonal Rl
     *
     * @param int $customerId
     */
    public function isAllowedNormalSeasonalRl($customerId)
    {
        $orderHistory = $this->getOrderHistory($customerId);
        $isAllowNormalRlOneMonth = $this->validateNormalRlOneMonth($customerId);
        if ($orderHistory['total_order_qty'] > 0 && $isAllowNormalRlOneMonth > 0) {
            $normalQty = 0;
            $seasonalQty = 0;
            $normal = true;
            $seasonal = true;
            foreach ($orderHistory['ordered_skus'] as $key => $value) {
                $normalQty += $this->normalRlQty($customerId, $key);
                $seasonalQty += $this->seasonalRlQty($customerId, $key);
            }

            $allowQty = $this->quantityHelper->getMaximumCartQty();
            if ($allowQty < $normalQty) {
                $normal = false;
            }
            if ($allowQty < $seasonalQty) {
                $seasonal = false;
            }
            return [
                'normal' => $normal ? true : false,
                'seasonal' => $seasonal ? true : false
            ];
        }
        return ['normal' => false, 'seasonal' => false];
    }

    /**
     * Validate Normal RL One Month
     *
     * @param int $customerId
     * @return int
     */
    public function validateNormalRlOneMonth($customerId)
    {
        $toDate = date('Y-m-d H:i:s', strtotime('-1 month', time()));
        $toDate = $this->timezoneInterface->convertConfigTimeToUtc($toDate);
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', ['lteq' => $toDate])
            ->addFieldToFilter('eda_order_type', 'ZOR')
            ->addFieldToFilter('status', 'complete');
        return $orderCollection->getSize();
    }

    /**
     * Round Qty
     *
     * @param int $averageOrderQty
     * @param int $orderedSkus
     * @return int|null
     */
    public function roundQty($averageOrderQty, $orderedSkus)
    {
        $averageOrderQty = (int)$averageOrderQty;
        $endOfValue = substr($averageOrderQty, -1);
        if ($endOfValue > 0) {
            $remainingValue = 10 - $endOfValue;
            $averageOrderQty = $averageOrderQty + $remainingValue;
            return $averageOrderQty;
        } else {
            return $averageOrderQty;
        }
    }

    /**
     * Normal RL Qty
     *
     * @param int $customerId
     * @param string $productSku
     * @return int|null
     */
    public function normalRlQty($customerId, $productSku)
    {
        $totalAverageOrderQty = 0;
        $orderHistory = $this->getOrderHistory($customerId);
        if ($orderHistory['total_order_qty'] > 0) {
            foreach ($orderHistory['ordered_skus'] as $sku => $qty) {
                if ($productSku == $sku) {
                    $totalAverageOrderQty = $qty/$orderHistory['total_order'];
                }
            }
            return $this->roundQty($totalAverageOrderQty, 1);
        }
    }

    /**
     * Seasonal RL qty
     *
     * @param int $customerId
     * @param string $productSku
     * @return int|null
     */
    public function seasonalRlQty($customerId, $productSku)
    {
        $adminRl = $this->requisitionList->create();
        $seasonal = $adminRl->getRlTypeData('seasonal');
        $orderHistory = $this->getOrderHistory($customerId);
        $normalQty = $this->normalRlQty($customerId, $productSku);
        if ($normalQty > 0 && $orderHistory['total_order_qty'] > 0) {
            $percentQty = $seasonal[0]['seasonal_percentage'];
            $seasonalRlPercent = ($normalQty/100 )* $percentQty;
            $totalOrderQtyWithPercent = $normalQty + $seasonalRlPercent;
            return $this->roundQty($totalOrderQtyWithPercent, 1);
        }
    }

    /**
     * Get product name
     *
     * @param int $requisitionListId
     * @param string $rlType
     * @param int $customerId
     * @return array|null
     */
    public function getProductName($requisitionListId, $rlType, $customerId)
    {
        $orderHistory = $this->getOrderHistory($customerId);
        $itemsData = $this->getAdminRequisitionList->getadminRequisitionProduct($requisitionListId);
        if ($itemsData['product_count'] > 0 && $rlType == 'other') {
            if (isset($itemsData['name']) && $itemsData['name'] !='') {
                return $itemsData['name'];
            }
        } elseif (!empty($orderHistory['product_name'])) {
            if ($rlType == 'normal') {
                return $orderHistory['product_name'][0];
            } elseif ($rlType == 'seasonal') {
                return $orderHistory['product_name'][0];
            }
        }
    }

    /**
     * Get Items Count
     *
     * @param int $requisitionListId
     * @param string $rlType
     * @param int $customerId
     * @return array|null
     */
    public function getItemsCount($requisitionListId, $rlType, $customerId)
    {
        $orderHistory = $this->getOrderHistory($customerId);
        $itemsData = $this->getAdminRequisitionList->getadminRequisitionProduct($requisitionListId);
        $productCount = 0;

        if ($itemsData['product_count'] > 0 && $rlType == 'other') {
            $productCount = $itemsData['product_count'];
        } elseif ($rlType == 'normal') {
            $productCount = count($orderHistory['ordered_skus']);
        } elseif ($rlType == 'seasonal') {
            $productCount = count($orderHistory['ordered_skus']);
        }
        return $productCount;
    }
}
