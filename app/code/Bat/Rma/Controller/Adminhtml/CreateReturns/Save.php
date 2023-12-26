<?php
namespace Bat\Rma\Controller\Adminhtml\CreateReturns;

use Bat\Rma\Helper\Data as RmaHelper;
use Bat\Rma\Model\NewRma\NewRmaModel;
use Bat\Sales\Model\SendOrderDetails;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Bat\Customer\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\Sales\Api\OrderRepositoryInterface;
use Bat\Sales\Helper\Data as SalesHelper;

/**
 * @class Save
 * Save Return Order Request
 */
class Save extends Action
{
    private PageFactory $resultPageFactory;
    private Data $helper;
    private Request $request;
    private RmaHelper $rmaHelper;
    private CustomerRepositoryInterface $customerRepositoryInterface;
    private NewRmaModel $newRmaModel;
    private SendOrderDetails $sendOrderDetails;
    private OrderRepositoryInterface $orderRepository;
    private SalesHelper $salesHelper;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param Request $request
     * @param RmaHelper $rmaHelper
     * @param NewRmaModel $newRmaModel
     * @param SendOrderDetails $sendOrderDetails
     * @param OrderRepositoryInterface $orderRepository
     * @param SalesHelper $salesHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $helper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        Request $request,
        RmaHelper $rmaHelper,
        NewRmaModel $newRmaModel,
        SendOrderDetails $sendOrderDetails,
        OrderRepositoryInterface $orderRepository,
        SalesHelper $salesHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->request = $request;
        $this->rmaHelper = $rmaHelper;
        $this->newRmaModel = $newRmaModel;
        $this->sendOrderDetails = $sendOrderDetails;
        $this->orderRepository = $orderRepository;
        $this->salesHelper = $salesHelper;
        parent::__construct($context);
    }

    /**
     * Submit new account closure request
     *
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $data = $this->getRequest()->getPostValue();
            if (!isset($data['outletId'])) {
                throw new LocalizedException(__('Outlet Id is required for creating returns'));
            }
            if (!isset($data['returnQty'])) {
                throw new LocalizedException(__('Please select products to initiate return'));
            }
            if (!isset($data['return_swift_code']) || $data['return_swift_code'] == '') {
                throw new LocalizedException(__('Return reason must be selected'));
            }
            $outletId = $data['outletId'];
            $returnQty = $data['returnQty'];
            $returnReason = $data['return_swift_code'];
            $collectionData = $this->helper->getCustomer('outlet_id', $outletId);
            if ($collectionData->getSize() > 0) {
                $customer = $collectionData->getFirstItem();
                $customer = $this->customerRepositoryInterface->getById($customer->getId());
                $accountStatus = '';
                if ($customer->getCustomAttribute('approval_status')) {
                    $accountStatus = $customer->getCustomAttribute('approval_status')->getValue();
                }
                if ($accountStatus != 1) {
                    throw new LocalizedException(
                        __('The Outlet with ID '.$outletId. ' should be an approved customer')
                    );
                }
                $orderCompleted = $this->rmaHelper->checkCustomerCompletedOrder($customer->getId());
                if (!$orderCompleted) {
                    throw new LocalizedException(
                        __('Customer has not completed any order')
                    );
                }
                $returnProducts = $this->validateReturnRequested($returnQty);
                if (!empty($returnProducts)) {
                    $returnOrder = $this->newRmaModel->createIroOrder($customer, $returnProducts, $returnReason);
                    if (!$returnOrder['success']) {
                        throw new LocalizedException(__($returnOrder['message']));
                    }
                    $this->rmaHelper->updateIroRequestOrder(
                        $returnOrder['order_id'],
                        $outletId,
                        'Return In Progress'
                    );
                    $iroOrder = $this->orderRepository->get($returnOrder['return_order_id']);
                    $orderSent = $this->sendOrderDetails->processOrderSendToEda($iroOrder, 'OMS');
                    if($orderSent){
                        $this->salesHelper->sendReturnInProgressMessage($iroOrder);
                    }
                } else {
                    throw new LocalizedException(__('No products selected'));
                }
                $this->messageManager->addSuccessMessage(__(
                    'The Return Request order is created successfully Order Reference :'.$returnOrder['order_id']
                ));
                return $resultRedirect->setPath('returns/createreturns/index', ['_current' => true]);
            } else {
                throw new LocalizedException(__('Outlet do not exist'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error while trying to save data : '.$e->getMessage()));
        }
        return $resultRedirect->setPath('*/*/addnew', ['_current' => true]);
    }

    /**
     * Validate Products requested for return
     *
     * @param $returnQty
     * @return array
     * @throws LocalizedException
     */
    public function validateReturnRequested($returnQty)
    {
        $returnRequestProducts = [];
        foreach ($returnQty as $productId => $requestedQty) {
            if (is_numeric($requestedQty) && $requestedQty > 0) {
                $returnRequestProducts[$productId] = $requestedQty;
            } elseif ($requestedQty != '' && (!is_numeric($requestedQty) || $requestedQty < 0)) {
                throw new LocalizedException(__('Requested quantity for return should be a positive number'));
            }
        }
        return $returnRequestProducts;
    }
}
