<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance\Order;

use Bat\CustomerBalance\Helper\Data;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Backend\Block\Template\Context;

/**
 * @api
 * @since 100.0.2
 */
class History extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'order/history.phtml';

    /**
     * @var Data
     */
    private Data $customerBalance;

    public function __construct(
        Context $context,
        Data $customerBalance,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->customerBalance = $customerBalance;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Return Available order balance to place order
     *
     * @return float|int|mixed|null
     */
    public function getAvailableBalanceForPlaceOrder()
    {
        return $this->customerBalance->getUsedCreditFromOrders(false, $this->getRequest()->getParam('id'));
    }

    /**
     * Return Unpaid orders total
     *
     * @return float|int|mixed
     */
    public function getUnpaidOrdersTotal()
    {
        return $this->customerBalance->getUnpaidOrdersTotal($this->getRequest()->getParam('id'));
    }

    /**
     * Return Unconfirmed orders total
     *
     * @return float|int|mixed|null
     */
    public function getUnconfirmedOrdersTotal()
    {
        return $this->customerBalance->getUnconfirmedOrdersTotal($this->getRequest()->getParam('id'));
    }
}
