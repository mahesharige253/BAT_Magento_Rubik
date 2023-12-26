<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\OrderRepository as OrderRepositoryModel;
use Bat\CustomerGraphQl\Helper\Data;
use Bat\SalesGraphQl\Helper\Data as SalesGraphQlHelperData;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Orders data resolver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderCollectionByCustomer
{

    /**
     * @var CollectionFactory
     */
    protected $orderCollection;

    /**
     * @var OrderRepositoryModel
     */
    protected $orderRepositoryModel;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var SalesGraphQlHelperData
     */
    protected $salesGraphQlHelperData;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

     /**
      * @var OrderFactory
      */
    protected $orderFactory;

    /**
     * Contructor
     *
     * @param CollectionFactory $orderCollection
     * @param OrderRepositoryModel $orderRepositoryModel
     * @param Data $data
     * @param SalesGraphQlHelperData $salesGraphQlHelperData
     * @param ResourceConnection $resourceConnection
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        CollectionFactory $orderCollection,
        OrderRepositoryModel $orderRepositoryModel,
        Data $data,
        SalesGraphQlHelperData $salesGraphQlHelperData,
        ResourceConnection $resourceConnection,
        OrderFactory $orderFactory
    ) {
        $this->orderCollection = $orderCollection;
        $this->orderRepositoryModel = $orderRepositoryModel;
        $this->data = $data;
        $this->salesGraphQlHelperData = $salesGraphQlHelperData;
        $this->resourceConnection = $resourceConnection;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Get order collection by customer id
     *
     * @param String $customerId
     * @param Array $arguments
     */
    public function getOrderCollectionByCustomerId($customerId, $arguments)
    {
        $response = [];
        $resultItmes = [];
        $firstItemTitle = '';
        $parentOutletId = '';
        $filterType = [];
        if (isset($arguments['filter']['filter_type']) && $arguments['filter']['filter_type'] != '') {
            $orderFilterType = $arguments['filter']['filter_type'];
            if ($orderFilterType == 'sales') {
                $filterType = ['eq'=>'ZOR'];
            } elseif ($orderFilterType == 'returns') {
                $filterType = [['eq'=>'IRO'],['eq'=>'ZRE1']];
            } elseif ($orderFilterType == 'cancelled') {
                $filterType = ['eq'=>'ZLOB'];
            }
        }
        $customerData = $this->data->getCustomer($customerId);
        $isCustomerParent = $customerData['is_parent'];
        if (!empty($customerData['outlet_id'])) {
            $parentOutletId = $customerData['outlet_id'];
        } else {
            throw new GraphQlInputException(__('Outlet Id not found'));
        }

        $conn = $this->resourceConnection->getConnection();

        $regularQuery = $conn->select()
            ->from(
                ['c' => 'sales_order'],
                ['entity_id']
            )
            ->where(
                "c.customer_id = ".$customerId
            );

        $bulkQuery = $conn->select()
            ->from(
                ['c' => 'sales_order'],
                ['entity_id']
            )
            ->where(
                "c.parent_outlet_id = ".$parentOutletId
            )->where(
                "c.outlet_id != ".$parentOutletId
            )->group("bulkorder_id");

        $conn->select()->reset();
        if ($isCustomerParent == 1) {
            $resultResp =  $conn->select()->union([$regularQuery, $bulkQuery]);
            $collectionResult = $conn->fetchAll($resultResp);
        } else {
            $collectionResult = $conn->fetchAll($regularQuery);
        }

        $entityId = [];
        foreach ($collectionResult as $collectionVal) {
            $entityId[] = $collectionVal['entity_id'];
        }

        $collection = $this->orderCollection->create()
         ->addFieldToSelect('*')
         ->addAttributeToFilter('entity_id', ['in' => $entityId])
            ->addFieldToFilter('is_return_order_created', [['eq'=>'0'],['eq'=>'']]);

        if (!empty($filterType)) {
            $collection->addFieldToFilter('eda_order_type', $filterType);
        }

        $filterStatus =[];
        if (isset($arguments['filter']['status']) && $arguments['filter']['status'] !='') {
            $filterStatus = explode(',', $arguments['filter']['status']);
            if (in_array('npi', $filterStatus)) {
                $collection->addFieldToFilter('order_type', ['eq' => 'npi']);
            } else {
                $collection->addFieldToFilter('order_type', ['nin' => ['npi']]);
            }
            $collection->addFieldToFilter('status', ['in' => $filterStatus]);
        }
        if (isset($arguments['filter']['order_type']) && !empty($arguments['filter']['order_type'])) {
            $collection->addFieldToFilter('order_type', ['in' => $arguments['filter']['order_type']]);
        }
        if (isset($arguments['filter']['date_from']) && ($arguments['filter']['date_from'] !='')
            && isset($arguments['filter']['date_to']) && ($arguments['filter']['date_to'] !='')) {
            $collection->addFieldToFilter('created_at', ['gteq' => $arguments['filter']['date_from']]);
            $collection->addFieldToFilter('created_at', ['lteq' => $arguments['filter']['date_to']." 23:59:59"]);
        }
         $collection->setPageSize($arguments['pageSize']);
         $collection->setCurPage($arguments['currentPage']);
        if (isset($arguments['filter']['sort'])) {
            $collection->setOrder(
                'created_at',
                $arguments['filter']['sort']
            );
        }
        $collection->load();
        $totalOrders = $collection->getSize();

        foreach ($collection as $key => $collectionData) {
            $result = [];
            $outletCount = 0;
            $orderGrandTotal = 0;
            $itemCount = 0;
            $orderData = $this->orderRepositoryModel->get($collectionData['entity_id']);
            $orderItems = $orderData->getAllItems();
            $result['order_id'] = $collectionData['entity_id'];

            $createdDate = date("Y/m/d", strtotime($collectionData['created_at']));
            $result['created_at'] = $createdDate;
            $result['grand_total'] = $collectionData['grand_total'];
            $result['status'] = $collectionData['status'];
            foreach ($orderItems as $item) {
                if (!in_array($item->getIsPriceTag(), [1, 2, 3])) {
                    $firstItemTitle = $item->getName();
                    break;
                }
            }
            foreach ($orderItems as $items) {
                if (!in_array($items->getIsPriceTag(), [1, 2, 3])) {
                    $itemCount++;
                }
            }
            if ($itemCount > 1) {
                $itemsCount = $itemCount - 1;
                $firstItemTitle = $firstItemTitle.' and '.$itemsCount.' more';
            }
            $isParent = $this->salesGraphQlHelperData->checkIncrementIdIsParent($collectionData['increment_id']);

            if (($collectionData['outlet_id'] != $collectionData['parent_outlet_id'])
                && $collectionData['bulkorder_id'] != '' && $isCustomerParent == 1) {
                $collectionData['increment_id'] = $collectionData['bulkorder_id'];
                $collectionData['status'] = '';
                $collectionData['is_bulk_order'] = true;
                $outletAndItemsCount = $this->getBulkOrderfirstItemAndCounts($collectionData['bulkorder_id']);
                $orderGrandTotal = $outletAndItemsCount['grand_total'];
                $outletCount = $outletAndItemsCount['outletsCount'];
                $firstItemTitle = $outletAndItemsCount['itemsCount'];
            } else {
                if ($collectionData['order_grand_total'] == null) {
                    $orderGrandTotal = $collectionData['grand_total'];
                } else {
                    $orderGrandTotal = $collectionData['order_grand_total'];
                }
            }
            $isBulkOrder = ($outletCount > 0 ) ? true : false;
            $result =  [
                  "id" => $collectionData['entity_id'],
                  "increment_id" => $collectionData['increment_id'],
                  "order_date" => $createdDate,
                  "status" => $collectionData['status'],
                  "item_name" => $firstItemTitle,
                  "grand_total" => $orderGrandTotal,
                  "order_type" => $collectionData['order_type'],
                  "total_outlets" => $outletCount,
                  "is_parent" => $isParent,
                  "is_bulk_order" => $isBulkOrder,
                  "model" => $orderData
               ];
            $resultItmes[] = $result;
        }

        $response['items'] = $resultItmes;
        $response['total_orders'] = $totalOrders;
        return $response;
    }

    /**
     * Get Bulk order outlet count and quantity
     *
     * @param String $bulkOrderId
     * @return Array
     */
    public function getBulkOrderfirstItemAndCounts($bulkOrderId)
    {

        $outletCounts = 0;
        $itemCount = 0;
        $grandTotal = 0;
        $firstItemTitle = '';
        $conn = $this->resourceConnection->getConnection();
        $select = $conn->select()
            ->from(
                ['c' => 'bat_bulkorder'],
                ['increment_id']
            )
            ->where(
                "c.bulkorder_id = ".$bulkOrderId
            );
            $resultCollection = $conn->fetchAll($select);
            $outletCounts = count($resultCollection);
        foreach ($resultCollection as $resultCollectionData) {
            $orderCollection = $this->orderFactory->create()->loadByIncrementId($resultCollectionData['increment_id']);
            //$grandTotal += $orderCollection->getOrderGrandTotal();
            $grandTotal += $orderCollection->getSubTotalInclTax() - $orderCollection->getDiscountAmount()*-1;
            foreach ($orderCollection->getAllItems() as $items) {
                if (!($items->getIsPriceTag())) {
                    $itemCount++;
                }
            }
            foreach ($orderCollection->getAllItems() as $item) {
                $firstItemTitle = $item->getName();
                break;
            }
        }
        if ($itemCount > 1) {
                $itemCount = --$itemCount;
        }
            $firstItemTitle = $firstItemTitle.' and '.$itemCount.' more';
            return ['outletsCount' => $outletCounts, 'itemsCount' => $firstItemTitle, 'grand_total' => $grandTotal];
    }
}
