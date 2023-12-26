<?php

namespace Bat\Sales\Block\Adminhtml\Order\View;

use Magento\Sales\Api\Data\OrderInterface;
use Bat\Sales\Model\EdaOrderType;
use Magento\Sales\Model\Order\Address;
use Bat\Rma\Model\Source\ReturnSwiftCode;

/**
 * @class Info
 * Display order information
 */
class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{
    /**
     * @var OrderInterface
     */
    private OrderInterface $order;

    /**
     * @var ReturnSwiftCode
     */
    private ReturnSwiftCode $returnSwiftCode;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        Address\Renderer $addressRenderer,
        OrderInterface $order,
        ReturnSwiftCode $returnSwiftCode,
        array $data = []
    ) {
        $this->order = $order;
        $this->returnSwiftCode = $returnSwiftCode;
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $groupRepository,
            $metadata,
            $elementFactory,
            $addressRenderer,
            $data
        );
    }

    /**
     * Return  order url
     *
     * @param string $orderIncrementId
     */
    public function getOrderUrl($orderIncrementId)
    {
        $order = $this->order->loadByIncrementId($orderIncrementId);
        return $this->getViewUrl($order->getId());
    }

    /**
     * Return order return type
     *
     * @return string
     */
    public function getReturnOrderType()
    {
        return EdaOrderType::ZREONE;
    }

    /**
     * Return order reason
     *
     * @param string $orderReason
     */
    public function getOrderReasonType($orderReason)
    {
        $reason = '';
        if ($orderReason == '001') {
            $reason = 'Fresh';
        } elseif ($orderReason == '201') {
            $reason = 'Old';
        } elseif ($orderReason == '151') {
            $reason = 'Damage';
        }
        return $reason;
    }

    /**
     * Return swift reason
     *
     * @param string $returnReason
     */
    public function getReturnReason($returnReason)
    {
        $swiftCodes =  $this->returnSwiftCode->getReturnSwiftCodeLabel();
        if (isset($swiftCodes[$returnReason])) {
            return $swiftCodes[$returnReason];
        }
        return '';
    }
}
