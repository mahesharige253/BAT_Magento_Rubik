<?php
namespace Bat\Rma\Helper;

use Bat\Rma\Model\ResourceModel\ZreResource;
use Bat\Rma\Model\ResourceModel\ZreResource\ZreCollectionFactory;
use Bat\Rma\Model\ZreModelFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Bat\Rma\Model\ResourceModel\IroResource;
use Bat\Rma\Model\ResourceModel\IroResource\IroCollectionFactory;
use Bat\Rma\Model\IroModelFactory;
use Psr\Log\LoggerInterface;
use Bat\Sales\Helper\Data as SalesHelper;

/**
 * @class Data
 *
 * Helper class for new products
 */
class Data extends AbstractHelper
{

    /**
     * @var UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $_orderCollectionFactory;

    /**
     * @var IroModelFactory
     */
    private IroModelFactory $iroModelFactory;

    /**
     * @var IroCollectionFactory
     */
    private IroCollectionFactory $iroCollectionFactory;

    /**
     * @var IroResource
     */
    private IroResource $iroResource;

    /**
     * @var ZreCollectionFactory
     */
    private ZreCollectionFactory $zreCollectionFactory;

    /**
     * @var ZreResource
     */
    private ZreResource $zreResource;

    /**
     * @var ZreModelFactory
     */
    private ZreModelFactory $zreModelFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @param Context $context
     * @param UrlInterface $backendUrl
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $orderCollectionFactory
     * @param IroModelFactory $iroModelFactory
     * @param IroCollectionFactory $iroCollectionFactory
     * @param IroResource $iroResource
     * @param ZreCollectionFactory $zreCollectionFactory
     * @param ZreResource $zreResource
     * @param ZreModelFactory $zreModelFactory
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     * @param SalesHelper $salesHelper
     */
    public function __construct(
        Context $context,
        UrlInterface $backendUrl,
        StoreManagerInterface $storeManager,
        CollectionFactory $orderCollectionFactory,
        IroModelFactory $iroModelFactory,
        IroCollectionFactory $iroCollectionFactory,
        IroResource $iroResource,
        ZreCollectionFactory $zreCollectionFactory,
        ZreResource $zreResource,
        ZreModelFactory $zreModelFactory,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        SalesHelper $salesHelper
    ) {
        parent::__construct($context);
        $this->_backendUrl = $backendUrl;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->iroModelFactory = $iroModelFactory;
        $this->iroCollectionFactory = $iroCollectionFactory;
        $this->iroResource = $iroResource;
        $this->zreCollectionFactory = $zreCollectionFactory;
        $this->zreResource = $zreResource;
        $this->zreModelFactory = $zreModelFactory;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->salesHelper = $salesHelper;
    }

    /**
     * Get products tab Url in admin
     *
     * @return string
     */
    public function getProductsGridUrl()
    {
        return $this->_backendUrl->getUrl('returns/createreturns/products', ['_current' => true]);
    }

    /**
     * Get Config path
     *
     * @param string $path
     * @return string|int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check If customer completed an order
     *
     * @param string $customerId
     */
    public function checkCustomerCompletedOrder($customerId)
    {
        $collection = $this->_orderCollectionFactory->create($customerId)
            ->addFieldToSelect('status')->addFieldToFilter('status', ['eq' => 'complete']);
        if ($collection->getSize()) {
            return true;
        }
        return false;
    }

    /**
     * Get order for IRO Update
     *
     * @param string $orderId
     * @return DataObject|string
     */
    public function getOrderForIroUpdate($orderId)
    {
        $collection =  $this->iroCollectionFactory->create()->addFieldToSelect('*')
            ->addFieldToFilter('order_id', ['eq'=>$orderId]);
        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }
        return '';
    }

    /**
     * Add Iro Request order in custom table
     *
     * @param string $returnOrder
     * @param null $status
     */
    public function updateIroRequestOrder($returnOrder, $outletId, $status = null)
    {
        $iroOrder = $this->getOrderForIroUpdate($returnOrder);
        if ($iroOrder != '') {
            $iroOrder->setStatus($status);
            $this->iroResource->save($iroOrder);
        } else {
            $iroOrder = $this->iroModelFactory->create();
            $iroOrder->setData(
                ['order_id' => $returnOrder, 'outlet_id' => $outletId]
            );
            $this->iroResource->save($iroOrder);
        }
    }

    /**
     * Save RMA Data for creating return orders
     *
     * @param $rmaData
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function updateRmaForZreOrderCreate($rmaData)
    {
        $zreOrder = $this->zreModelFactory->create();
        $zreOrder->setData($rmaData);
        $this->zreResource->save($zreOrder);
    }

    /**
     * Return Zre Pending orders
     *
     * @return mixed
     */
    public function getZrePendingOrders()
    {
        $maxFailuresAllowed = $this->getConfig('bat_integrations/bat_order/eda_return_order_max_failures_allowed');
        return $this->zreCollectionFactory->create()
            ->addFieldToFilter('order_created', ['eq' => 0])
            ->addFieldToFilter('order_in_progress', ['eq' => 0])
            ->addFieldToFilter('failure_attempts', ['lteq' => $maxFailuresAllowed]);
    }


    /**
     * Enable/Disable Product
     *
     * @param array $productIds
     * @param int $status
     * @return bool
     */
    public function enableDisableProduct($productIds,$status)
    {
        $success = false;
        try{
            $adminStoreId = $this->salesHelper->getAdminStoreId();
            foreach ($productIds as $productId){
                $product = $this->productRepository->getById($productId);
                $product->setStatus($status);
                $product->setStoreId($adminStoreId);
                $this->productRepository->save($product);
            }
            $success = true;
        }catch (\Exception $e){
            $this->logger->info('Product Disable Exception :'.$e->getMessage());
            $success = false;
        }
        return $success;
    }
}
