<?php

namespace Bat\BulkOrder\Block\Adminhtml;

use Magento\Framework\App\Request\DataPersistorInterface;
use Bat\BulkOrder\Model\GetBulkOrderReviewDetails;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Bat\BulkOrder\Model\GetBulkOrderItemDiscountMessage;

class BulkorderReview extends \Magento\Backend\Block\Template
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
     * @var GetBulkOrderItemDiscountMessage
     */
    protected $getItemMessage;

     /**
      * Construct method
      *
      * @param Context $context
      * @param Registry $coreRegistry
      * @param DataPersistorInterface $dataPersistor
      * @param GetBulkOrderReviewDetails $getReviewDetails
      * @param GetBulkOrderItemDiscountMessage $getItemMessage
      * @param array $data
      */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        DataPersistorInterface $dataPersistor,
        GetBulkOrderReviewDetails $getReviewDetails,
        GetBulkOrderItemDiscountMessage $getItemMessage,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->dataPersistor = $dataPersistor;
        $this->getReviewDetails = $getReviewDetails;
        $this->getItemMessage = $getItemMessage;
        parent::__construct($context, $data);
    }

    /**
     * GetBulkOrderDetails Function
     */
    public function getBulkOrderParentDetails()
    {
        $parentId = $this->dataPersistor->get('parent_id');
        $reviewDetails = $this->getReviewDetails->getBulkOrderDetails($parentId, 'parent');
        return $reviewDetails;
    }
    
    /**
     * GetBulkOrderOutletDetails Function
     */
    public function getBulkOrderOutletDetails()
    {
        $parentId = $this->dataPersistor->get('parent_id');
        $reviewDetails = $this->getReviewDetails->getBulkOrderDetails($parentId, 'outlet');
        return $reviewDetails;
    }

    /**
     * GetPlaceOrderUrl Function
     */
    public function getPlaceOrderUrl()
    {
        return $this->getUrl('bulkorder/bulkorder/placeorder');
    }

     /**
     * GetItemMessage Function
     */
    public function getItemMessage($itemId,$sku)
    {
        $message = $this->getItemMessage->getItemDiscountMessage($itemId,$sku);
        $messageData = $message;
        if($message != '') {
            if(!is_array($message)) {
                $message = json_decode($message);
                if($message->context == 'benefit') {
                    $messageData = 'You Received Benefit'; 
                } else {
                   $messageData = 'Buy '.$message->sku.' more than '.$message->price.' , and get '.$message->discount. ' KRW discount';
                }

            }
        }
        return $messageData;
    }
}
