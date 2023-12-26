<?php

namespace Bat\Sales\Model\Api;

use Bat\Sales\Api\OrderConfirmationInterface;
use Bat\Sales\Helper\Data;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Sales\Model\EdaOrderType;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Bat\Sales\Model\BatOrderStatus;
use Bat\JokerOrder\Model\JokerOrderCancellation;
use Bat\Sales\Model\SendOrderDetails;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;

/**
 * @class ConfirmOrder
 * Order Confirmation update
 */
class ConfirmOrder implements OrderConfirmationInterface
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
     * @var Data
     */
    private Data $dataHelper;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

      /**
       * @var JokerOrderCancellation
       */
    private JokerOrderCancellation $jokerOrderCancellation;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

     /**
     * @var GetDiscountMessage
     */
    private GetDiscountMessage $getDiscountMessage;

    /**
     * @param Order $order
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $dataHelper
     * @param EdaOrdersResource $edaOrdersResource
     * @param JokerOrderCancellation $jokerOrderCancellation
     * @param EdaOrdersFactory $edaOrdersFactory
     * @param SendOrderDetails $sendOrderDetails
     * @param GetDiscountMessage $getDiscountMessage
     */
    public function __construct(
        Order $order,
        OrderRepositoryInterface $orderRepository,
        Data $dataHelper,
        EdaOrdersResource $edaOrdersResource,
        JokerOrderCancellation $jokerOrderCancellation,
        EdaOrdersFactory $edaOrdersFactory,
        SendOrderDetails $sendOrderDetails,
        GetDiscountMessage $getDiscountMessage
    ) {
        $this->order = $order;
        $this->orderRepository = $orderRepository;
        $this->dataHelper = $dataHelper;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->jokerOrderCancellation = $jokerOrderCancellation;
        $this->edaOrdersFactory = $edaOrdersFactory;
        $this->sendOrderDetails = $sendOrderDetails;
        $this->getDiscountMessage = $getDiscountMessage;
    }

    /**
     * Confirm order
     *
     * @param mixed $data
     * @return array[]
     */
    public function confirmOrder($data)
    {
        $result = ['success' => true, 'message' => ''];
        try {
            $this->addLog("====================================================");
            $this->addLog("Request : ");
            $this->addLog(json_encode($data));
            $this->addLog("Response : ");
            $this->validateInput($data);
            $orderData = $this->order->loadByIncrementId($data['increment_id']);
            $customerId = $orderData->getCustomerId();
            if (!$orderData->getId()) {
                throw new LocalizedException(__('Order not found'));
            }
            $order = $this->orderRepository->get($orderData->getId());
            $orderType = $order->getEdaOrderType();
            if ($orderType == EdaOrderType::ZREONE || $orderType == EdaOrderType::IRO) {
                throw new LocalizedException(__('Order confirmation not allowed for '.$orderType.' order'));
            }
            $sapOrderStatus = $order->getSapOrderStatus();
            $order->setBatchId($data['batch_id']);
            $order->setUpdatedDate($data['updated_date']);
            $order->setCountryCode($data['country_code']);
            $order->setSapCountryCode($data['sap_country_code']);
            $order->setSapOrderNumber($data['sap_order_number']);
            $order->setSapOrderStatus($data['sap_order_status']);
            if (isset($data['sap_credit_status']) && (trim($data['sap_credit_status']) != '')) {
                $order->setSapCreditStatus($data['sap_credit_status']);
            }
            if (isset($data['order_reject_reason']) && (trim($data['order_reject_reason']) != '')) {
                $order->setOrderRejectReason($data['order_reject_reason']);
            }
            if (isset($data['order_reject_desc']) && (trim($data['order_reject_desc']) != '')) {
                $order->setOrderRejectDesc($data['order_reject_desc']);
            }
            if($orderType == EdaOrderType::ZLOB){
                $zlobStatus[] = $this->deliveryFailOrderStatusUpdate($order,$data,$sapOrderStatus);
                $this->addLog(json_encode($zlobStatus));
                return $zlobStatus;
            }
            if ($data['sap_order_status'] == '001') {
                if ($order->canCancel()) {
                    $order->cancel();
                    $order->addCommentToStatusHistory(
                        __('Order Cancelled By EDA')
                    )->setIsCustomerNotified(false);
                    $order->save();
                    $result['message'] = 'Order cancelled successfully';
                    $this->jokerOrderCancellation->returnJokerOrder($customerId, $data['increment_id']);
                    if($order->getDiscountAmount() != '' && $order->getAppliedRuleIds() != ''){
                        $this->getDiscountMessage->setCustomerTimesUsed($customerId, $order->getAppliedRuleIds());
                    }
                } else {
                    throw new LocalizedException(__('Order cannot be cancelled'));
                }
            } elseif ($data['sap_order_status'] == '000') {
                if ($order->getStatus() != 'canceled') {
                    if ($sapOrderStatus == '000') {
                        throw new LocalizedException(__('The order is already confirmed'));
                    }
                    if ($order->getGrandTotal() != $order->getTotalPaid() || $order->getTotalDue() != 0) {
                        throw new LocalizedException(__('Payment not completed'));
                    }
                    $order->setStatus(BatOrderStatus::PREPARING_TO_SHIP_STATUS);
                    $order->setState(BatOrderStatus::PENDING_STATE);
                    $order->addCommentToStatusHistory(
                        __('Order Confirmed By EDA')
                    )->setIsCustomerNotified(false);
                    $order = $order->save();
                    $channel = 'OMS';
                    $this->dataHelper->updateOrderToEda(
                        $order->getEntityId(),
                        $order->getEdaOrderType(),
                        $channel,
                        $order->getIncrementId()
                    );
                    $this->sendOrderDetails->processOrderSendToEda($order, $channel);
                    $result['message'] = 'Order confirmation status updated successfully';
                } else {
                    throw new LocalizedException(__('Canceled order cannot be confirmed'));
                }
            } elseif ($data['sap_order_status'] == '002') {
                if ($order->getStatus() == BatOrderStatus::FAILURE_STATUS) {
                    throw new LocalizedException(__('The order is already set to failure status'));
                }
                if (!$order->hasInvoices()) {
                    $order->addCommentToStatusHistory(
                        __('Order failure updated By EDA')
                    )->setIsCustomerNotified(false);
                    $order->setStatus(BatOrderStatus::FAILURE_STATUS);
                    $order->setState(BatOrderStatus::PENDING_STATE);
                    $order->save();
                    $result['message'] = 'Order updated for Failure';
                    if($order->getDiscountAmount() != '' && $order->getAppliedRuleIds() != ''){
                        $this->getDiscountMessage->setCustomerTimesUsed($order->getCustomerId(), $order->getAppliedRuleIds());
                    }
                } else {
                    if ($order->getStatus() == 'canceled') {
                        throw new LocalizedException(__('Failure cannot be set for canceled order'));
                    } else {
                        throw new LocalizedException(__('Failure cannot be set for confirmed order'));
                    }
                }
            }
        } catch (LocalizedException $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        } catch (\Exception $e) {
            $result['success'] = false;
            $this->addLog($e->getMessage());
            $result['message'] = 'Something went wrong';
        }
        $this->addLog(json_encode($result));
        return [$result];
    }

    /**
     * Eda order confirmation log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addlog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/EdaOrderConfirmation.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }

    /**
     * Validate Input
     *
     * @param mixed $data
     * @throws LocalizedException
     */
    public function validateInput($data)
    {
        $errorMsg = [];
        if (!isset($data['increment_id']) || (trim($data['increment_id']) == '')) {
            $errorMsg[] = 'Order Number is required and value should be specified';
        }
        if (!isset($data['batch_id']) || (trim($data['batch_id']) == '')) {
            $errorMsg[] = 'batch_id is required and value should be specified';
        }
        if (!isset($data['updated_date']) || (trim($data['updated_date']) == '')) {
            $errorMsg[] = 'updated_date is required and value should be specified';
        }
        if (!isset($data['country_code']) || (trim($data['country_code']) == '')) {
            $errorMsg[] = 'country_code is required and value should be specified';
        }
        if (!isset($data['sap_country_code']) || (trim($data['sap_country_code']) == '')) {
            $errorMsg[] = 'sap_country_code is required and value should be specified';
        }
        if (!isset($data['sap_order_number']) || (trim($data['sap_order_number']) == '')) {
            $errorMsg[] = 'sap_order_number is required and value should be specified';
        }
        if (!isset($data['sap_order_status']) || (trim($data['sap_order_status']) == '')) {
            $errorMsg[] = 'sap_order_status is required and value should be specified';
        } else {
            $statusFound = false;
            $confirmationStatus = ["001","000","002"];
            foreach ($confirmationStatus as $status) {
                if ($data['sap_order_status'] ===  $status) {
                    $statusFound = true;
                }
            }
            if (!$statusFound) {
                $errorMsg[] = 'Allowed values for sap_order_status is 000,001,002';
            }
        }
        if (!empty($errorMsg)) {
            throw new LocalizedException(__(implode(', ', $errorMsg)));
        }
    }

    /**
     * Update ZLOB order confirmation status
     *
     * @param OrderInterface $order
     * @param mixed $data
     */
    public function deliveryFailOrderStatusUpdate($order,$data,$previousSapOrderStatus)
    {
        $result['success'] = true;
        $result['message'] = 'Order confirmation status updated successfully';
        $sapOrderStatus = $data['sap_order_status'];
        if($previousSapOrderStatus == "000"){
            $result['success'] = false;
            $result['message'] = 'Order completion status updated already';
            return $result;
        } else {
            if($sapOrderStatus == "000"){
                $order->setStatus(BatOrderStatus::ZLOB_COMPLETE_STATUS);
                $order->setState(BatOrderStatus::COMPLETE_STATE);
                $order->addCommentToStatusHistory(
                    __('Order Completed By EDA')
                )->setIsCustomerNotified(false);
                $order->save();
            } else {
                $order->setStatus(BatOrderStatus::ZLOB_IN_PROGRESS_STATUS);
                $order->setState(BatOrderStatus::PENDING_STATE);
                $order->addCommentToStatusHistory(
                    __('Order Updated By EDA')
                )->setIsCustomerNotified(false);
                $order->save();
            }
        }
        return $result;
    }
}
