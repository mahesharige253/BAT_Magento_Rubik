<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Bat\Rma\Block\Adminhtml\Order\View\Tab;

use Bat\Rma\Block\Adminhtml\Rma\ReturnSwiftCode\ReturnReason;
use Bat\Rma\Model\Source\ReturnSwiftCode;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Framework\Registry;
use Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory;
use Magento\Rma\Model\RmaFactory;

/**
 * Order RMA Grid
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Rma extends \Magento\Rma\Block\Adminhtml\Order\View\Tab\Rma
{
    /**
     * @var ReturnSwiftCode
     */
    private ReturnSwiftCode $returnSwiftCode;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param RmaFactory $rmaFactory
     * @param Registry $coreRegistry
     * @param \Magento\Rma\Helper\Data $rmaHelper
     * @param ReturnSwiftCode $returnSwiftCode
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        RmaFactory $rmaFactory,
        Registry $coreRegistry,
        \Magento\Rma\Helper\Data $rmaHelper,
        ReturnSwiftCode $returnSwiftCode,
        array $data = []
    ) {
        $this->returnSwiftCode = $returnSwiftCode;
        parent::__construct(
            $context,
            $backendHelper,
            $collectionFactory,
            $rmaFactory,
            $coreRegistry,
            $rmaHelper,
            $data
        );
    }

    /**
     * Prepare grid columns
     *
     * @return \Magento\Rma\Block\Adminhtml\Order\View\Tab\Rma
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        unset($this->_columns['order_increment_id']);
        unset($this->_columns['order_date']);
        $this->addColumn(
            'batch_id',
            [
                'header' => __('Batch Id'),
                'index' => 'batch_id',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );
        $this->addColumn(
            'return_swift_code',
            [
                'header' => __('Return Reason Swift Code'),
                'index' => 'return_swift_code',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name',
                'type' => 'options',
                'options' => $this->returnSwiftCode->getReturnSwiftCodeLabel(),
                'renderer' => ReturnReason::class
            ]
        );
        return $this;
    }
}
