<?php

declare(strict_types=1);

namespace Bat\PriceDecimal\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Dashboard\Period;
use Magento\Framework\Module\Manager;
use Magento\Reports\Model\ResourceModel\Order\Collection;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\Store;
use Bat\PriceDecimal\Helper\Data;

/**
 * Adminhtml dashboard totals bar
 * @api
 * @since 100.0.2
 */
class Totals extends \Magento\Backend\Block\Dashboard\Totals
{
    /**
     * @var string
     */
    protected $_template = 'Bat_PriceDecimal::dashboard/totalbar.phtml';

    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param Manager $moduleManager
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        Manager $moduleManager,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $collectionFactory, $moduleManager, $data);
    }

    /**
     * Get Total Format Price
     *
     * @param string $price
     */
    public function getTotalFormatPrice($price)
    {
        return $this->helper->getFormatedPrice($price);
    }
}
