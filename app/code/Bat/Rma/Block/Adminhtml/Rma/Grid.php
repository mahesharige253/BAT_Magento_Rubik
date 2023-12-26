<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\Rma\Block\Adminhtml\Rma;

use Bat\Rma\Block\Adminhtml\Rma\ReturnSwiftCode\ReturnReason;
use Bat\Rma\Model\Source\ReturnSwiftCode;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Rma\Model\ResourceModel\Rma\Grid\CollectionFactory;
use Magento\Rma\Model\RmaFactory;

/**
 * RMA Grid
 */
class Grid extends \Magento\Rma\Block\Adminhtml\Rma\Grid
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
     * @param ReturnSwiftCode $returnSwiftCode
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        RmaFactory $rmaFactory,
        ReturnSwiftCode $returnSwiftCode,
        array $data = []
    ) {
        $this->returnSwiftCode = $returnSwiftCode;
        parent::__construct(
            $context,
            $backendHelper,
            $collectionFactory,
            $rmaFactory,
            $data
        );
    }

    /**
     * Prepare grid columns
     *
     * @return \Magento\Rma\Block\Adminhtml\Rma\Grid
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
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
