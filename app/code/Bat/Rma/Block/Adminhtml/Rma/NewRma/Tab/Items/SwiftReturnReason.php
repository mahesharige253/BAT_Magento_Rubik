<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Request Details Block at RMA page
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Bat\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items;

use Bat\Rma\Model\Source\ReturnSwiftCode;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\AbstractGeneral;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @api
 * @since 100.0.2
 */
class SwiftReturnReason extends AbstractGeneral
{
    /**
     * @var ReturnSwiftCode
     */
    private ReturnSwiftCode $returnSwiftCode;
    private RmaRepositoryInterface $rmaRepository;
    private OrderRepositoryInterface $orderRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ReturnSwiftCode $returnSwiftCode
     * @param RmaRepositoryInterface $rmaRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ReturnSwiftCode $returnSwiftCode,
        RmaRepositoryInterface $rmaRepository,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->returnSwiftCode = $returnSwiftCode;
        $this->rmaRepository = $rmaRepository;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $registry, $data);
    }

    /**
     * Return return swift code options
     *
     * @return array[]
     */
    public function getSwiftCodes()
    {
        return $this->returnSwiftCode->toOptionArray();
    }

    public function isIroOrder()
    {
        $rmaId = $this->getRequest()->getParam('id');
        $rmaDetails = $this->rmaRepository->get($rmaId);
        $orderId = $rmaDetails->getOrderId();
        $order = $this->orderRepository->get($orderId);
        if ($order->getEdaOrderType() == 'IRO' && $rmaDetails->getStatus() == 'pending') {
            return true;
        }
        return false;
    }
}
