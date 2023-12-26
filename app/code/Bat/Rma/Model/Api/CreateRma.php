<?php
namespace Bat\Rma\Model\Api;

use Bat\Rma\Api\RmaCreateInterface;

use Bat\Sales\Model\SendOrderDetails;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Validator\EmailAddress;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\RmaFactory;
use Magento\RmaGraphQl\Model\Rma\Builder;
use Magento\RmaGraphQl\Model\Rma\Item\Builder as ItemBuilder;
use Magento\RmaGraphQl\Model\Validator;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Psr\Log\LoggerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zend_Log_Exception;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * @class CreateRma
 * EDA to M2 Rma create
 */
class CreateRma implements RmaCreateInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var RmaRepositoryInterface
     */
    private RmaRepositoryInterface $rmaRepository;

    /**
     * @var Builder
     */
    private Builder $builder;

    /**
     * @var Data
     */
    private Data $rmaHelper;

    /**
     * @var Validator
     */
    private Validator $validator;

    /**
     * @var ItemBuilder
     */
    private ItemBuilder $itemBuilder;

    /**
     * @var RmaFactory
     */
    private RmaFactory $rmaFactory;

    /**
     * @var CreditmemoManagementInterface
     */
    private CreditmemoManagementInterface $creditmemoManagement;

    /**
     * @var CreditmemoLoader
     */
    private CreditmemoLoader $creditMemoLoader;

    /**
     * @var OrderFactory
     */
    private OrderFactory $orderFactory;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param RmaRepositoryInterface $rmaRepository
     * @param Data $rmaHelper
     * @param Validator $validator
     * @param ItemBuilder $itemBuilder
     * @param RmaFactory $rmaFactory
     * @param CreditmemoManagementInterface $creditMemoManagement
     * @param CreditmemoLoader $creditMemoLoader
     * @param OrderFactory $orderFactory
     * @param SendOrderDetails $sendOrderDetails
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        RmaRepositoryInterface $rmaRepository,
        Data $rmaHelper,
        Validator $validator,
        ItemBuilder $itemBuilder,
        RmaFactory $rmaFactory,
        CreditmemoManagementInterface $creditMemoManagement,
        CreditmemoLoader $creditMemoLoader,
        OrderFactory $orderFactory,
        SendOrderDetails $sendOrderDetails,
        ResourceConnection $resource
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->rmaRepository = $rmaRepository;
        $this->rmaHelper = $rmaHelper;
        $this->validator = $validator;
        $this->itemBuilder = $itemBuilder;
        $this->rmaFactory = $rmaFactory;
        $this->creditmemoManagement = $creditMemoManagement;
        $this->creditMemoLoader = $creditMemoLoader;
        $this->orderFactory = $orderFactory;
        $this->sendOrderDetails = $sendOrderDetails;
        $this->resource = $resource;
    }

    /**
     * Create Rma
     *
     * @param string $batchId
     * @param string $orderType
     * @param string $createdAt
     * @param string $incrementId
     * @param mixed $items
     * @return mixed|void
     */
    public function createRma($batchId, $orderType, $createdAt, $incrementId, $items)
    {
        $result = [];
        try {
            $this->logRmaRequest('======================================================');
            $this->logRmaRequest('Request : ');
            $request = [
                'batch_id' => $batchId,
                'order_type'=>$orderType,
                'created_at'=>$createdAt,
                'increment_id'=>$incrementId,
                'items'=>$items
            ];
            $this->logRmaRequest(json_encode($request));
            $this->logRmaRequest('Response : ');
            $this->validateInput($incrementId, $items, $createdAt, $batchId);
            $orderEntityId = $this->validateOrder($incrementId, $items);
            $orderItems = $this->rmaHelper->getOrderItems($orderEntityId)->getItems();
            if (empty($orderItems)) {
                throw new LocalizedException(__('Order not eligible for returns'));
            }
            $reorderItems = $this->prepareRmaData($orderItems, $items);
            $saveRma = $this->saveRma($reorderItems, $incrementId, $orderEntityId, $createdAt, $batchId);
            if ($saveRma['success']) {
                $this->updateRma($saveRma['rma_created'], $orderEntityId);
                $result[] = ['success' => true, 'message'=>'RMA Created Successfully'];
            } else {
                $result[] = ['success' => false, 'message'=>'Something went wrong'];
            }
        } catch (\Exception $e) {
            $result[] = ['success' => false, 'message'=>$e->getMessage()];
        }
        $this->logRmaRequest(json_encode($result));
        return $result;
    }

    /**
     * Prepare data for RMA
     *
     * @param DataObject[] $orderItems
     * @param array $returnItems
     * @return array
     * @throws LocalizedException
     */
    public function prepareRmaData($orderItems, $returnItems)
    {
        $reorderItems = [];
        foreach ($orderItems as $item) {
            $itemExists = $this->getItemFromRequest($item->getId(), $returnItems);
            if ($itemExists) {
                $qtyOrdered = $item->getAvailableQty();
                $qtyRequestedForReturn = $itemExists['qty_requested'];
                if ($qtyRequestedForReturn < $qtyOrdered && $qtyOrdered > 0) {
                    if ($qtyRequestedForReturn == 0) {
                        $qtyTobeReturned = $qtyOrdered;
                    } else {
                        $qtyTobeReturned = $qtyOrdered - $qtyRequestedForReturn;
                        if ($qtyTobeReturned == 0) {
                            $qtyTobeReturned = $qtyOrdered;
                        }
                    }
                    $reorderItems['items'][] = [
                        "order_item_uid" => base64_encode($item->getId()),
                        "quantity_to_return" => $qtyTobeReturned
                    ];
                } elseif ($qtyRequestedForReturn > $qtyOrdered && $qtyOrdered != 0) {
                    $message = 'Max Qty allowed for return for Item Id '.$item->getId()
                        .' is '.$qtyOrdered;
                    throw new LocalizedException(__($message));
                }
            }
        }
        return $reorderItems;
    }

    /**
     * Validate input
     *
     * @param string $orderId
     * @param array $items
     * @param string $createdAt
     * @param string $batchId
     * @throws LocalizedException
     */
    public function validateInput($orderId, $items, $createdAt, $batchId)
    {
        if (empty(trim($orderId)) || empty($items) || empty(trim($createdAt)) || empty(trim($batchId))) {
            throw new LocalizedException(__('Please update all the required parameters'));
        }
    }

    /**
     * Validate order
     *
     * @param int $incrementId
     * @param array $items
     * @return mixed
     * @throws LocalizedException
     */
    public function validateOrder($incrementId, $items)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        if ($order->getId()) {
            $this->checkItemIdExists($order->getItems(), $items);
            return $order->getId();
        } else {
            throw new LocalizedException(__('Order not found'));
        }
    }

    /**
     * Check ItemId exists
     *
     * @param int $itemId
     * @param array $returnItems
     * @return false
     */
    public function getItemFromRequest($itemId, $returnItems)
    {
        $index = array_search($itemId, array_column($returnItems, 'entity_id'));
        if (is_numeric($index)) {
            return $returnItems[$index];
        }
        return false;
    }

    /**
     * Check ItemId exists
     *
     * @param OrderItemInterface[] $orderItems
     * @param array $returnItems
     * @throws LocalizedException
     */
    public function checkItemIdExists($orderItems, $returnItems)
    {
        $itemIds = [];
        foreach ($orderItems as $item) {
            $itemIds[] = $item->getId();
        }
        foreach ($returnItems as $item) {
            if (!isset($item['entity_id']) || trim($item['entity_id']) == '') {
                throw new LocalizedException(__('Item entity_id should be specified'));
            }
            if (!in_array($item['entity_id'], $itemIds)) {
                throw new LocalizedException(__('Item Id - '.$item['entity_id'].' does not exist'));
            }
            if (!isset($item['qty_requested']) || trim($item['qty_requested']) == '') {
                throw new LocalizedException(
                    __('Item Id - '.$item['entity_id'].' qty_requested value should be specified')
                );
            }
            if (($item['qty_requested'] < 0)) {
                throw new LocalizedException(__('Item Id - '.$item['entity_id'].' value cannot be less than zero'));
            }
        }
    }

    /**
     * Save Rma
     *
     * @param array $reorderItems
     * @param string $incrementId
     * @param int $orderEntityId
     * @param string $createdAt
     * @param string $batchId
     * @throws CouldNotSaveException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function saveRma($reorderItems, $incrementId, $orderEntityId, $createdAt, $batchId)
    {
        if (!empty($reorderItems)) {
            $reorderItems['order_uid'] = $incrementId;
            $orderData = $this->orderRepository->get($orderEntityId);
            $rma = $this->build($orderData, $reorderItems, $createdAt);
            $rma->setBatchId($batchId);
            $rma->setDateRequested($createdAt);
            $rma = $this->rmaRepository->save($rma);

            return ['success' => true, 'message'=>'RMA Created successfully','rma_created'=>$rma];
        } else {
            throw new LocalizedException(__('Returns already initiated or not required for the requested items'));
        }
    }

    /**
     * Build RMA object
     *
     * @param OrderInterface $order
     * @param array $rmaData
     * @param string $createdAt
     * @return RmaInterface
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function build(OrderInterface $order, array $rmaData, $createdAt): RmaInterface
    {
        $rma = $this->rmaFactory->create();
        $rma->setData(
            [
                'status' => Status::STATE_PENDING,
                'date_requested' => $createdAt,
                'order_id' => $order->getId(),
                'order_increment_id' => $order->getIncrementId(),
                'store_id' => $order->getStoreId(),
                'customer_id' => $order->getCustomerId(),
                'order_date' => $order->getCreatedAt(),
                'customer_name' => $order->getCustomerName()
            ]
        );
        $items = [];
        foreach ($rmaData['items'] as $item) {
            $items[] = $this->itemBuilder->build($item);
        }
        $this->validator->validateRequestedQty($items, (int)$order->getId());
        return $rma->setItems($items);
    }

    /**
     * Approve Created RMA
     *
     * @param RmaInterface $rma
     * @param int $orderEntityId
     */
    public function updateRma($rma, $orderEntityId)
    {
        $itemToCredit = [];
        foreach ($rma->getItems() as $item) {
            $qty = $item->getQtyRequested();
            $item->setStatus(Status::STATE_APPROVED);
            $item->setQtyApproved($qty);
            $item->setQtyAuthorized($qty);
            $item->setQtyReturned($qty);
            $itemToCredit[$item->getOrderItemId()] = ['qty'=>$qty];
        }
        $rma->setStatus(Status::STATE_PROCESSED_CLOSED);
        $this->rmaRepository->save($rma);
        $this->updateOrderType($orderEntityId);
        $this->createCreditMemo($orderEntityId, $itemToCredit);
        $this->sendOrderDetails->addOrderInEdaOrderUpdate($orderEntityId, 'ZRE');
    }

    /**
     * Create credit memo
     *
     * @param int $orderEntityId
     * @param array $itemToCredit
     * @throws LocalizedException
     */
    public function createCreditMemo($orderEntityId, $itemToCredit)
    {
        $creditMemoData = [];
        $creditMemoData['do_offline'] = true;
        $creditMemoData['shipping_amount'] = "0.00";
        $creditMemoData['adjustment_positive'] = "0.00";
        $creditMemoData['adjustment_negative'] = "0.00";
        $creditMemoData['comment_text'] = "";
        $creditMemoData['is_visible_on_front'] = 0;
        $creditMemoData['send_email'] = 0;
        $creditMemoData['refund_customerbalance_return_enable'] = 0;
        $creditMemoData['items'] = $itemToCredit;
        $this->creditMemoLoader->setOrderId($orderEntityId);
        $this->creditMemoLoader->setCreditmemo($creditMemoData);
        $creditmemo = $this->creditMemoLoader->load();
        if ($creditmemo) {
            if (!$creditmemo->isValidGrandTotal()) {
                throw new LocalizedException(
                    __('The credit memo\'s total must be positive.')
                );
            }
            $creditmemoManagement = $this->creditmemoManagement;
            $doOffline = $creditMemoData['do_offline'];
            $creditmemoManagement->refund($creditmemo, $doOffline);
        }
    }

    /**
     * Rma Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function logRmaRequest($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaRma.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    public function updateOrderType($orderId)
    {
        if ($orderId !='') {
            $connection  = $this->resource->getConnection();
            $data = ["order_type" => __("Return Order")];
            $where = ['entity_id = ?' => (int)$orderId];
            $tableName = $connection->getTableName("sales_order");
            $connection->update($tableName, $data, $where);
        }
    }
}
