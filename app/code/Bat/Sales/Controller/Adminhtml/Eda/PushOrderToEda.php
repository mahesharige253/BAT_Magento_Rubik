<?php
namespace Bat\Sales\Controller\Adminhtml\Eda;

use Bat\Sales\Model\BatOrderStatus;
use Bat\Sales\Model\EdaOrdersFactory;
use Bat\Sales\Model\ResourceModel\EdaOrdersResource;
use Bat\Sales\Model\SendOrderDetails;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Bat\Sales\Helper\Data as SalesHelper;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @class PushOrderToEda
 * Send Order to EDA
 */
class PushOrderToEda extends Action
{
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @var EdaOrdersResource
     */
    private EdaOrdersResource $edaOrdersResource;

    /**
     * @var EdaOrdersFactory
     */
    private EdaOrdersFactory $edaOrdersFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Request $request
     * @param SalesHelper $salesHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param SendOrderDetails $sendOrderDetails
     * @param EdaOrdersResource $edaOrdersResource
     * @param EdaOrdersFactory $edaOrdersFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Request $request,
        SalesHelper $salesHelper,
        OrderRepositoryInterface $orderRepository,
        SendOrderDetails $sendOrderDetails,
        EdaOrdersResource $edaOrdersResource,
        EdaOrdersFactory $edaOrdersFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->salesHelper = $salesHelper;
        $this->orderRepository = $orderRepository;
        $this->sendOrderDetails = $sendOrderDetails;
        $this->edaOrdersResource = $edaOrdersResource;
        $this->edaOrdersFactory = $edaOrdersFactory;
        parent::__construct($context);
    }

    /**
     * Update order to EDA
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParams();
        $orderId = '';
        $channel = '';
        try {
            $channel = $data['channel'];
            $orderId = $data['order_id'];
            $order = $this->orderRepository->get($orderId);
            $orderSent = $this->sendOrderDetails->pushOrderToEda($order, $channel);
            if ($orderSent) {
                $order->addCommentToStatusHistory(
                    __('Updated order to EDA for Channel : '.$channel)
                )->setIsCustomerNotified(false);
                if ($order->getEdaOrderType() == 'ZOR' && $channel == 'SWIFTPLUS') {
                    if ($order->getSapOrderStatus() == '' && $order->getAction() == '') {
                        $order->setState(BatOrderStatus::PENDING_STATE)
                            ->setStatus(BatOrderStatus::PREPARING_TO_SHIP_STATUS);
                    }
                }
                $order->save();
                $this->messageManager->addSuccessMessage(__('Request sent successfully'));
            } else {
                $this->messageManager->addErrorMessage(__('Request could not be sent'));
            }
            $edaOrder = $this->salesHelper->getOrderForEdaUpdate($order->getEntityId(), $channel);
            if ($edaOrder != '') {
                if ($orderSent) {
                    $edaOrder->setOrderSent(1);
                } else {
                    $edaOrder->setFailureAttempts($edaOrder->getFailureAttempts() + 1);
                }
                $this->edaOrdersResource->save($edaOrder);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Request Failed: '.$e->getMessage()));
        }
        return $resultRedirect->setPath('sales/order/view', ['_current' => true,'order_id' => $orderId]);
    }
}
