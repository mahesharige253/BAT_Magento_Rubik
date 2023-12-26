<?php
 
namespace Bat\BulkOrder\Block\Adminhtml;

use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\BulkOrder\Model\GetBulkOrderReviewDetails;
use Magento\Framework\App\ResourceConnection;
use Bat\SalesGraphQl\Model\OrderPaymentDeadline;
use Magento\Sales\Model\OrderFactory;
 
/**
 * Class BulkOrderForm
 */
class PlaceOrder extends \Magento\Backend\Block\Template
{

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var GetBulkOrderReviewDetails
     */
    protected $getReviewDetails;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var OrderPaymentDeadline
     */
    private $orderPaymentDeadline;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * Construct method
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param GetBulkOrderReviewDetails $getReviewDetails
     * @param ResourceConnection $resourceConnection
     * @param OrderPaymentDeadline $orderPaymentDeadline
     * @param OrderFactory $orderFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        GetBulkOrderReviewDetails $getReviewDetails,
        ResourceConnection $resourceConnection,
        OrderPaymentDeadline $orderPaymentDeadline,
        OrderFactory $orderFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->dataPersistor = $dataPersistor;
        $this->getReviewDetails =$getReviewDetails;
        $this->resourceConnection = $resourceConnection;
        $this->orderPaymentDeadline = $orderPaymentDeadline;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get BulkOrderId
     */
    public function getBulkOrderId()
    {
        return $this->dataPersistor->get('bulkorder_id');
    }

    /**
     * Get BulkOrderDetails
     */
    public function getBulkOrderDetails()
    {

        $bulkOrderId = $this->getBulkOrderId();

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
        $bulkOrderPlacedData = [];
        if (!empty($bulkOrderData)) {
            $i = 0;
            foreach ($bulkOrderData as $key => $childData) {
                $collection = $this->orderFactory->create()->loadByIncrementId($childData['increment_id']);
                $orderData = $collection->getData();
                $paymentDeadline = $this->orderPaymentDeadline->getThankyouPagePaymentDeadline($orderData['entity_id']);
                $deadlineDate = date('Y년 m월 d일, 오후 11시', strtotime($paymentDeadline));
                $bulkOrderPlacedData[$i]['outlet_id'] = $orderData['outlet_id'];
                $bulkOrderPlacedData[$i]['order_id'] = $childData['increment_id'];
                $bulkOrderPlacedData[$i]['payment_deadline'] = $deadlineDate;
                $i++;
            }
        }

        return $bulkOrderPlacedData;
    }
}
