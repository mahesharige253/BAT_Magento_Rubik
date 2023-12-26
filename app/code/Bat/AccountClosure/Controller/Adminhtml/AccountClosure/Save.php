<?php
namespace Bat\AccountClosure\Controller\Adminhtml\AccountClosure;

use Bat\Rma\Helper\Data as RmaHelper;
use Bat\Sales\Model\SendOrderDetails;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Bat\Customer\Helper\Data;
use Bat\GetCartGraphQl\Helper\Data as QuantityHelper;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\BankCardUpload;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File;
use Bat\AccountClosure\Model\AccountClosureProductReturnFactory;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Image\AdapterFactory;
use Bat\Kakao\Model\Sms as KakaoSms;
use \Magento\Catalog\Model\ProductFactory;
use Bat\CustomerGraphQl\Model\ReturnRequestOrder;
use Bat\AccountClosure\Model\ClosureFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @class Save
 * Save RequisitionList Details
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var QuantityHelper
     */
     protected $quantityHelper;

    /**
     * @var BankCardUpload
     */
    private $bankCardUpload;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @var Magento\Framework\Filesystem
     */
    private $fileSystem;

    /**
     * @var DriverFile
     */
    private $driverFile;

     /**
      * @var Magento\Framework\Filesystem\Io\File
      */
    private $fileDriver;

    /**
     * @var AccountClosureProductReturnFactory
     */
    private $accountClosureProductReturnFactory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

     /**
      * @var AdapterFactory
      */
    protected $adapterFactory;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

     /**
      * @var ProductFactory
      */
    private $product;

    /**
     * @var ReturnRequestOrder
     */
    private $returnRequest;

    /**
     * @var ClosureFactory
     */
    private $closure;

    /**
     * @var RmaHelper
     */
    private RmaHelper $rmaHelper;

    /**
     * @var SendOrderDetails
     */
    private SendOrderDetails $sendOrderDetails;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $helper
     * @param QuantityHelper $quantityHelper
     * @param BankCardUpload $bankCardUpload
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param Filesystem $fileSystem
     * @param DriverFile $driverFile
     * @param File $fileDriver
     * @param AccountClosureProductReturnFactory $accountClosureProductReturn
     * @param Request $request
     * @param UploaderFactory $uploaderFactory
     * @param AdapterFactory $adapterFactory
     * @param KakaoSms $kakaoSms
     * @param ProductFactory $product
     * @param ReturnRequestOrder $returnRequest
     * @param ClosureFactory $closure
     * @param RmaHelper $rmaHelper
     * @param SendOrderDetails $sendOrderDetails
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $helper,
        QuantityHelper $quantityHelper,
        BankCardUpload $bankCardUpload,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        Filesystem $fileSystem,
        DriverFile $driverFile,
        File $fileDriver,
        AccountClosureProductReturnFactory $accountClosureProductReturn,
        Request $request,
        UploaderFactory $uploaderFactory,
        AdapterFactory $adapterFactory,
        KakaoSms $kakaoSms,
        ProductFactory $product,
        ReturnRequestOrder $returnRequest,
        ClosureFactory $closure,
        RmaHelper $rmaHelper,
        SendOrderDetails $sendOrderDetails,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->quantityHelper = $quantityHelper;
        $this->bankCardUpload = $bankCardUpload;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->fileSystem = $fileSystem;
        $this->fileDriver = $fileDriver;
        $this->accountClosureProductReturnFactory = $accountClosureProductReturn;
        $this->request = $request;
        $this->adapterFactory = $adapterFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->kakaoSms = $kakaoSms;
        $this->product = $product;
        $this->returnRequest = $returnRequest;
        $this->closure = $closure;
        $this->rmaHelper = $rmaHelper;
        $this->sendOrderDetails = $sendOrderDetails;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Submit new account closure request
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $data = $this->getRequest()->getPostValue();

            $outletId = $data['outletId'];

            if (isset($data['customerId'])) {

                $customerFactory = $this->_customerRepositoryInterface->getById($data['customerId']);

                $approvalPreviousStatus = $customerFactory->getCustomAttribute(
                    'approval_status'
                )->getValue();
                $currentApprovalStatus = $data['approval_status'];

                if (isset($data['approval_status']) && $data['approval_status'] == 9) {
                    $customerFactory->setCustomAttribute(
                        'approval_status',
                        $data['approval_status']
                    );
                }

                if ($approvalPreviousStatus != 7) {
                    if (isset($data['approval_status']) && $data['approval_status'] != '') {
                        $customerFactory->setCustomAttribute(
                            'approval_status',
                            $data['approval_status']
                        );
                    }
                    if (isset($data['disclosure_rejected_fields']) && $data['disclosure_rejected_fields'] != '') {
                        if ($data['approval_status'] != 8) {
                            $customerFactory->setCustomAttribute(
                                'disclosure_rejected_fields',
                                ''
                            );
                        } else {
                            $customerFactory->setCustomAttribute(
                                'disclosure_rejected_fields',
                                implode(',', $data['disclosure_rejected_fields'])
                            );
                        }
                    }
                    if (isset($data['disclosure_rejected_reason']) && $data['disclosure_rejected_reason'] != '') {
                        $customerFactory->setCustomAttribute(
                            'disclosure_rejected_reason',
                            $data['disclosure_rejected_reason']
                        );
                    }

                    if (isset($data['returnQuantity']) && $data['returnQuantity'] !='') {

                        $selectedPrdRtn = $data['returnQuantity'];
                        $qtyReturnRequested = 0;
                        $i = 0;
                        $firstProductName = '';
                        $productSku = [];
                            foreach ($selectedPrdRtn as $key => $qty) {
                                $productSku[$key]['sku'] = $this->getProductSku($key);
                                $productSku[$key]['qty'] = $qty;
                                $qtyReturnRequested += $qty;
                                if ($i == 0) {
                                    $firstProductName = $this->getFirstProductName($key);
                                }
                                $i++;
                            }

                        if ($i > 1) {
                            $firstProductName = $firstProductName.' 외 '.(--$i).' 개';
                        }

                        if ($approvalPreviousStatus != 6 && $currentApprovalStatus == 6 && $approvalPreviousStatus == 14
                        ) {
                            $productReturnModel = $this->accountClosureProductReturnFactory->create();
                            $orderId = $this->returnRequest->createOrder($productSku, $customerFactory->getId());
                            if (isset($orderId['success']) && $orderId['success'] == 1) {
                                $orderNumber = $orderId['order_id'];
                                $productReturnModel->updateReturnOrder($customerFactory->getId(), $orderNumber);
                                $this->rmaHelper->updateIroRequestOrder(
                                    $orderId['order_id'],
                                    $customerFactory->getCustomAttribute('outlet_id')->getValue()
                                );
                                $returnOrder = $this->orderRepository->get($orderId['return_order_id']);
                                $this->sendOrderDetails->processOrderSendToEda($returnOrder,'OMS');
                            } else {
                                if ($orderId['error']) {
                                    $this->messageManager->addErrorMessage(__(
                                        'Error while trying to save data : '.$orderId['msg']
                                    ));
                                    return $resultRedirect->setPath('*/*/index');
                                }
                            }
                        }

                    }
                }
                $this->_customerRepositoryInterface->save($customerFactory);
                $this->messageManager->addSuccessMessage(__(
                    'The status for outlet '.$outletId.' has been successfully updated'
                ));
            } else {

                $collectionData = $this->helper->getCustomer('outlet_id', $outletId);
                if ($collectionData->getSize() > 0) {
                    $customer = $collectionData->getFirstItem();
                    $customerFactory = $this->_customerRepositoryInterface->getById($customer->getId());
                    $customerFactory->setCustomAttribute('approval_status', 14);
                    $customerFactory->setCustomAttribute('disclosure_consent_form_selected', 1);
                    $customerFactory->setCustomAttribute('closure_request_admin', 1);
                    if (isset($data['account_closing_date']) && $data['account_closing_date'] != '') {
                        $customerFactory->setCustomAttribute('account_closing_date', $data['account_closing_date']);
                    }
                    if (isset($data['returning_stock']) && $data['returning_stock'] != '') {
                        $customerFactory->setCustomAttribute('returning_stock', $data['returning_stock']);
                    }
                    $files = $this->request->getFiles()->toArray();
                    if (isset($files["bank_account_card"]) && $files["bank_account_card"]["error"] == 0) {
                        $bankImage = $this->uploadBankCardImage($files["bank_account_card"], $customer->getId());
                        if ($bankImage != 0) {
                            $filePath = '/bankCard/'.$bankImage;
                            $customerFactory->setCustomAttribute('bank_account_card', $filePath);
                        }
                    }

                    //Check for product return table and delete if any customer data
                    $productReturnModel = $this->accountClosureProductReturnFactory->create();
                    $productReturnModel->checkProductReturnData($customer->getId());

                    if (isset($data['returnQty']) && $data['returnQty'] !='') {
                        $selectedQtys = $data['returnQty'];
                        $productSku = [];
                        $orderProcess = 0;
                        foreach ($selectedQtys as $key => $qty) {
                            if ($qty != '') {
                                $ids[] = $key;
                                $productSku[$key]['sku'] = $this->getProductSku($key);
                                $productSku[$key]['qty'] = $qty;
                                $productReturnModel = $this->accountClosureProductReturnFactory->create();

                                $productReturnModel->setData('outlet_id', $outletId);
                                $productReturnModel->setData('customer_id', $customer->getId());
                                $productReturnModel->setData('product_id', $key);
                                $productReturnModel->setData('qty', $qty);
                                $productReturnModel->save();
                                $orderProcess = 1;
                            }
                        }
                    }

                    $this->_customerRepositoryInterface->save($customerFactory);

                    // Track Closure Account details
                    $closureModel = $this->closure->create();
                    $existData = $closureModel->getIdbyCustomerId($customer->getId());
                    if (count($existData)  == 0) {
                        $closureModel->setCustomerId($customer->getId());
                        $closureModel->save();
                    }

                }

                 $this->messageManager->addSuccessMessage(__(
                     'The Request for outlet '.$outletId.' has been successfully submitted'
                 ));
            }
            return $resultRedirect->setPath('*/*/index', ['_current' => true]);

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error while trying to save data'.$e->getMessage()));
        }
        return $resultRedirect->setPath('*/*/addnew', ['_current' => true]);
    }

    /**
     * Function to upload image
     *
     * @param String $bankImage
     * @param Int $customerId
     * @return Int
     */
    public function uploadBankCardImage($bankImage, $customerId)
    {

        $mediaPath     = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $originalPath  = 'customer/bankCard/';
        $mediaFullPath = $mediaPath . $originalPath;
        if (!$this->fileDriver->fileExists($mediaFullPath)) {
            $this->fileDriver->mkdir($mediaFullPath, 0775);
        }

        $file_name = $customerId.'_'.rand().'_' . $bankImage['name'];
        $file_tmp = $bankImage['tmp_name'];

        $uploaderFactory = $this->uploaderFactory->create(['fileId' => 'bank_account_card']);
        $uploaderFactory->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
        $imageAdapter = $this->adapterFactory->create();
        $uploaderFactory->setAllowRenameFiles(true);
        $result = $uploaderFactory->save($mediaFullPath, $file_name);
        return $file_name;
    }

    /**
     * Get Selected Item
     *
     * @param array $selectedItem
     * @param array $data
     * @return array
     */
    protected function getSelectedItem($selectedItem, $data)
    {
        $filteredArray = array_intersect_key($data, array_flip($selectedItem));
        return $filteredArray;
    }

    /**
     * Get FirstProductName
     *
     * @param int $id
     * @return string
     */
    protected function getFirstProductName($id)
    {
        $product = $this->product->create()->load($id);
        return $product->getName();
    }

    /**
     * Get Sku
     *
     * @param int $id
     * @return string
     */
    protected function getProductSku($id)
    {
        $product = $this->product->create()->load($id);
        return $product->getSku();
    }
}
