<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Bat\SalesGraphQl\Model\OrderPaymentDeadline;
use Bat\CustomerGraphQl\Helper\Data;
use Magento\Eav\Model\Config;
use Magento\Sales\Model\OrderFactory;
use Bat\CustomerBalanceGraphQl\Helper\Data as CustomerBalanceHelper;
use Bat\BulkOrder\Model\CreateBulkCart;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Bat\SalesGraphQl\Helper\Data as SalesHelper;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\Pricing\Helper\Data as PricingData;

class ParentOrderDetail
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var OrderPaymentDeadline
     */
    private $orderPaymentDeadline;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var \Magento\Eav\Model\Config $eavConfig
     */
    protected $_eavConfig;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var CustomerBalanceHelper
     */
    protected $customerBalanceHelper;

    /**
     * @var CreateBulkCart
     */
    protected $bulkOrderData;

    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * @var ProductRepositoryInterfaceFactory
     */
    private $_productRepositoryFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var SalesHelper
     */
    private $salesHelper;

    /**
     * @var CompanyManagementInterface
     */
    private $companyRepository;

    /**
     * @var PricingData
     */
    protected $pricingHelper;

    /**
     * @param ResourceConnection $resourceConnection
     * @param OrderPaymentDeadline $orderPaymentDeadline
     * @param Data $data
     * @param Config $eavConfig
     * @param OrderFactory $orderFactory
     * @param CustomerBalanceHelper $customerBalanceHelper
     * @param CreateBulkCart $bulkCartdata
     * @param CartDetails $cartDetails
     * @param ProductRepositoryInterfaceFactory $productRepositoryFactory
     * @param SalesHelper $salesHelper
     * @param CompanyManagementInterface $companyRepository
     * @param PricingData $pricingData
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        OrderPaymentDeadline $orderPaymentDeadline,
        Data $data,
        Config $eavConfig,
        OrderFactory $orderFactory,
        CustomerBalanceHelper $customerBalanceHelper,
        CreateBulkCart $bulkCartdata,
        CartDetails $cartDetails,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        SalesHelper $salesHelper,
        CompanyManagementInterface $companyRepository,
        PricingData $pricingHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->orderPaymentDeadline = $orderPaymentDeadline;
        $this->data = $data;
        $this->_eavConfig = $eavConfig;
        $this->orderFactory = $orderFactory;
        $this->customerBalanceHelper = $customerBalanceHelper;
        $this->bulkOrderData = $bulkCartdata;
        $this->cartDetails = $cartDetails;
        $this->_productRepositoryFactory = $productRepositoryFactory;
        $this->salesHelper = $salesHelper;
        $this->companyRepository = $companyRepository;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * @inheritdoc
     */
    public function getParentOrderDetail($bulkOrderId, $customerId, $websiteId)
    {
        $result = $childOrderList = $childOrderListData = [];
        $ordersGrandTotal = 0;
        $orderTotalDiscount = 0;
        $totalOutlets = 0;
        $totalItemsQty = 0;
        $remainingAr = 0;
        $overPayment = 0;
        $totalArLimit = 0;
        $minimumPayment = 0;
        $ordersSubTotal = 0;
        $childOrderSubtotal = 0;
        $isCreditCustomer = 0;
        $totalStoreItemsCount = 0;
        $totalStoreItemsQty = 0;

        $tableName = $this->resourceConnection->getTableName('bat_bulkorder');
        $connection = $this->resourceConnection->getConnection();
        $path = 'general/locale/code';
        $scope = 'default';
        $select = $connection->select()
            ->from(
                ['bbo' => $tableName],
                ['*']
            )->where(
                "bbo.bulkorder_id = " . $bulkOrderId
            );
        $bulkOrderData = $connection->fetchAll($select);
        $totalOutlets = count($bulkOrderData);
        if (!empty($bulkOrderData)) {
            $parentOutletId = $bulkOrderData[0]['parent_outlet_id'];
            $parentId = $this->cartDetails->getCustomerIdsByCustomAttribute($parentOutletId);
            $isParentCreditCustomer = $this->customerBalanceHelper->isCreditCustomer($parentId[0]);

            foreach ($bulkOrderData as $key => $childIncrementId) {
                $collection = $this->orderFactory->create()->loadByIncrementId($childIncrementId['increment_id']);
                $orderData = $collection->getData();
                $currentCustomerId = $orderData['customer_id'];
                $firstPaymentDeadline = $this->orderPaymentDeadline->getThankyouPagePaymentDeadline($orderData['entity_id']);
                $paymentDeadline = $this->orderPaymentDeadline->getPaymentDeadline($orderData['entity_id']);
                $childVbaDetail = $this->getVirtualBankDetail($currentCustomerId, $bulkOrderData[0]['bankname'], $bulkOrderData[0]['virtual_account']);
                $ordersGrandTotal += $orderData['order_grand_total'];
                $remainingAr += $orderData['remaining_ar'];
                $overPayment += $orderData['over_payment'];
                $orderTotalDiscount += $this->pricingHelper->currency($orderData['discount_amount'], false, false);
                $ordersSubTotal += $collection->getSubTotalInclTax();

                /* Child order overpayment,minimumPayment and remaining ar details */
                $childOrderSubtotal = $collection->getSubTotalInclTax();
                $isCreditCustomer = $this->customerBalanceHelper->isCreditCustomer($currentCustomerId);
                $childOutletName = $this->getCompanyName($currentCustomerId);
                $isCustomerFirstOrder = $this->customerBalanceHelper->getIsCustomerFirstOrder($currentCustomerId);

                $minimumAmount = 0;
                if ($orderData['remaining_ar'] != null && $orderData['order_grand_total'] > $orderData['remaining_ar']) {
                     $minimumAmount = $orderData['order_grand_total'] - $orderData['remaining_ar'];
                }
                if ($minimumAmount <= 0) {
                    $minimumAmount = 0;
                }
                $minimumPayment += $minimumAmount;
                $returnArray = $this->salesHelper->getShippingAddress($currentCustomerId);

                /* Child order list */
                $createdDate = date("Y/m/d", strtotime($orderData['created_at']));
                $orderItems = $collection->getAllItems();
                $ti = [];
                $finalData = [];
                $arr = [];
                $defaultTextAttributeVal = '';
                $attributeLabel = '';
                $selectedAttributeVal = '';
                $childItemsCount = 0;
                $itemqty = 0;
                $totalQuantity = 0;
                foreach ($orderItems as $key => $item) {
                    if (!in_array($item['is_price_tag'], [1, 2])) {
                        $itemqty = $item['qty_ordered'];
                        $totalQuantity += $itemqty;
                        $childItemsCount++;
                    }
                    $productData = $this->_productRepositoryFactory->create()->getById($item['product_id']);
                    $attributeCode = $productData->getBatDefaultAttribute();

                    $attribute = $productData->getResource()->getAttribute($attributeCode);
                    if ($attribute) {
                        if (in_array(
                            $productData->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                            ['select']
                        ) ||
                            in_array(
                                $productData->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                                ['boolean']
                            )
                        ) {
                            $selectedAttributeVal = $productData->getAttributeText($attributeCode);
                            $attributeLabel = $productData->getResource()->getAttribute($attributeCode)->
                                getFrontendLabel();
                        } else {
                            $attributeLabel = $productData->getResource()->getAttribute($attributeCode)->
                                getFrontendLabel();
                            $selectedAttributeVal = $productData->getData($attributeCode);
                        }
                    }
                    if ($selectedAttributeVal != '') {
                        $defaultTextAttributeVal = $attributeLabel . ': ' . $selectedAttributeVal;
                    }
                    $name = $item->getName();
                    $qty = $item['qty_ordered'];
                    $price = $item['price'];
                    $subtotal = $item['row_total'];
                    $isPriceTag = $item['is_price_tag'];
                    $ti['name'] = $name;
                    $ti['qty'] = $qty;
                    $ti['price'] = $price;
                    $ti['subtotal'] = $subtotal;
                    $ti['default_attribute'] = $defaultTextAttributeVal;
                    $ti['is_price_tag'] = $isPriceTag;
                    $finalData[] = $ti;
                    $totalItemsQty += $item->getQtyOrdered();
                    if ($key == 0) {
                        $firstItemTitle = $item->getName();
                    }
                    $itemsCount = 0;
                    if (count($orderItems) > 1 && $key == 0) {
                        $itemsCount = $this->getItemsCount($orderItems);
                        if ($itemsCount > 1) {
                            $firstItemTitle = $firstItemTitle . ' and ' . $itemsCount -1 . ' more';
                        }
                    }

                    $childOrderList['id'] = $orderData['entity_id'];
                    $childOrderList['outlet_name'] = $childOutletName;
                    $childOrderList['increment_id'] = $orderData['increment_id'];
                    $childOrderList['order_type'] = $orderData['order_type'];
                    $childOrderList['order_date'] = $createdDate;
                    $childOrderList['status'] = $orderData['status'];
                    $childOrderList['is_first_order'] = $isCustomerFirstOrder;
                    $childOrderList['is_credit_customer'] = $isCreditCustomer;
                    $childOrderList['item_name'] = $firstItemTitle;
                    $childOrderList['grand_total'] = $this->pricingHelper->currency($orderData['order_grand_total'], false, false);
                    $childOrderList['discounts'] = $this->pricingHelper->currency($orderData['discount_amount'], false, false);
                    $childOrderList['overpayment'] = $this->pricingHelper->currency($orderData['over_payment'], false, false);
                    $childOrderList['remaining_ar'] = $this->pricingHelper->currency($orderData['remaining_ar'], false, false);
                    $childOrderList['minimum_amount'] = $this->pricingHelper->currency($minimumAmount, false, false);
                    $childOrderList['subtotal'] = $this->pricingHelper->currency($childOrderSubtotal, false, false);
                    $childOrderList['net'] = $orderData['subtotal'];
                    $childOrderList['vat'] = $orderData['grand_total'] - $orderData['subtotal'];
                    $childOrderList['childItems_count'] = $childItemsCount;
                    $childOrderList['total_qty'] = $totalQuantity;
                    $childOrderList['virtual_bank_account'] = $childVbaDetail;
                    $childOrderList['shipping_addresses']['firstname'] = $returnArray['firstname'];
                    $childOrderList['shipping_addresses']['lastname'] = $returnArray['lastname'];
                    $childOrderList['shipping_addresses']['city'] = $returnArray['city'];
                    $childOrderList['shipping_addresses']['street']['street1'] = $returnArray['street1'];
                    $childOrderList['shipping_addresses']['street']['street2'] = $returnArray['street2'];
                    $childOrderList['shipping_addresses']['postcode'] = $returnArray['postcode'];
                    $childOrderList['shipping_addresses']['region'] = $returnArray['region'];
                    $childOrderList['shipping_addresses']['telephone'] = $returnArray['telephone'];
                    $childOrderList['shipping_addresses']['country']['code'] = $returnArray['country'];
                    $childOrderList['shipping_addresses']['country']['label'] = $returnArray['country'];
                    $deliveredDate = $collection->getActionDate();
                    if($deliveredDate != ''){
                        $deliveredDate = date_format(date_create($deliveredDate),'Y/m/d');
                    }
                    $childOrderList['delivery_details']['delivery_date'] = $collection->getShipDate();
                    $childOrderList['delivery_details']['delivered_date'] = $deliveredDate;
                    $childOrderList['delivery_details']['tracking_number'] = $collection->getAwbNumber();
                    $childOrderList['delivery_details']['tracking_url'] = $collection->getTrackingUrl();
                    $childOrderList['return_details']['return_date'] = $collection->getShipDate();
                    $childOrderList['return_details']['tracking_number'] = $collection->getAwbNumber();
                    $childOrderList['return_details']['tracking_url'] = $collection->getTrackingUrl();
                    $childOrderList['return_details']['is_shipment_available'] = $collection->getIsShipmentAvailable();
                    $childOrderList['return_details']['returned_date'] = $deliveredDate;
                    $childOrderList['delivery_details']['is_shipment_available'] = $collection->getIsShipmentAvailable();


                    /* end Child order list */
                }
                $totalStoreItemsCount += $childItemsCount;
                $totalStoreItemsQty += $totalQuantity;
                foreach ($finalData as $finaldat) {
                    $arr[] = [
                        'title' => $finaldat['name'],
                        'quantity' => $finaldat['qty'],
                        'price' => $finaldat['price'],
                        'subtotal' => $finaldat['subtotal'],
                        'default_attribute' => $finaldat['default_attribute'],
                        'is_price_tag' => $finaldat['is_price_tag']
                    ];
                }
                $childOrderList['items'] = $arr;
                $childOrderListData[] = $childOrderList;
                $parentPriceSummery = [
                    'subtotal' => $ordersSubTotal,
                    'remaining_ar' => $remainingAr,
                    'overpayment' => $overPayment,
                    'minimum_amount' => $minimumPayment,
                    'grand_total' => $ordersGrandTotal
                ];

                $net = round((int) $ordersSubTotal / 1.1);
                $vat = $ordersSubTotal - $net;
                $orderAmount = ['net' => round($net), 'vat' => $vat, 'discounts' => $orderTotalDiscount, 'total' => $ordersSubTotal - $orderTotalDiscount *-1, 'subtotal' => $ordersSubTotal];
                $vbaDetail = $this->getVirtualBankDetail($customerId, $bulkOrderData[0]['bankname'], $bulkOrderData[0]['virtual_account']);
                $parentOutletQty['outlet_count'] = $totalOutlets;
                $parentOutletQty['items_count'] = $totalStoreItemsCount;
                $parentOutletQty['items_quantity'] = $totalStoreItemsQty;
                $result['order_date'] = $bulkOrderData[0]['created_at'];
                $result['order_type'] = __('Sales Order');
                $result['is_credit_customer'] = $isParentCreditCustomer;
                $result['bulk_order_id'] = $bulkOrderId;
                $result['items_count'] = '';
                $result['first_payment_deadline_date'] = $firstPaymentDeadline;
                $result['payment_deadline_date'] = $paymentDeadline;
                $result['message'] = 'Once we receive your Payment, We will send you a message and arrange shipping';
                $result['order_amount'] = $orderAmount;
                $result['virtual_bank_account'] = $vbaDetail;
                $result['parent_order_quantity'] = $parentOutletQty;
                $result['child_order_list'] = $childOrderListData;
                $result['total'] = $parentPriceSummery;
            }
            return $result;
        }
    }

    /**
     * Get Remaning items Count in ODP
     *
     * @param array $orderItems
     * @return int
     */
    public function getItemsCount($orderItems)
    {
        $remainingItemsCounts = 0;
        foreach ($orderItems as $key => $item) {
            if (!in_array($item['is_price_tag'], [1,2])) {
                $remainingItemsCounts++;
            }
        }
        return $remainingItemsCounts;
    }

    /**
     * Get Virtual Bank Detail
     *
     * @param int $customerId
     * @param string $bankName
     * @param string $virtualAccount
     * @return array|string
     */
    public function getVirtualBankDetail($customerId, $bankName, $virtualAccount)
    {
        $vbaDetail['bank_name'] = $bankName;
        $vbaDetail['account_number'] = $virtualAccount;
        $vbaDetail['account_holder_name'] = $this->getCompanyName($customerId);
        return $vbaDetail;
    }

    /**
     * Get Company Name(Outlet name)
     *
     * @param int $customerId
     * @return string
     */
    public function getCompanyName($customerId)
    {
        $company = $this->companyRepository->getByCustomerId($customerId);
        return ($company) ? $company->getCompanyName() : '';
    }

    /**
     * Get Attribute label by Value
     *
     * @param string $attributeCode
     * @param string $value
     * @return array|string
     */
    public function getAttributeLabelByValue($attributeCode, $value)
    {
        try {
            $entityType = $this->_eavConfig->getEntityType('customer');
            $attribute = $this->_eavConfig->getAttribute('customer', $attributeCode);
            $options = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
