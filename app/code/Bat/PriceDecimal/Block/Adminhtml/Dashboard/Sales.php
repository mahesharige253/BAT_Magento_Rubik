<?php
declare(strict_types=1);

namespace Bat\PriceDecimal\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\Manager;
use Magento\Reports\Model\ResourceModel\Order\CollectionFactory;
use Bat\PriceDecimal\Helper\Data;

class Sales extends \Magento\Backend\Block\Dashboard\Sales
{
    /**
     * @var string
     */
    protected $_template = 'Bat_PriceDecimal::dashboard/salebar.phtml';

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
     * Get UpdatedFormat Price
     *
     * @param string $price
     */
    public function getUpdatedFormatPrice($price)
    {
        return $this->helper->getFormatedPrice($price);
    }
}
