<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bat\Rma\Block\Adminhtml\Rma\ReturnSwiftCode;

use Bat\Rma\Model\Source\ReturnSwiftCode;
use Magento\Backend\Block\Context;

class ReturnReason extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var ReturnSwiftCode
     */
    private ReturnSwiftCode $returnSwiftCodes;

    /**
     * @param Context $context
     * @param ReturnSwiftCode $returnSwiftCodes
     * @param array $data
     */
    public function __construct(
        Context         $context,
        ReturnSwiftCode $returnSwiftCodes,
        array           $data = []
    ) {
        $this->returnSwiftCodes = $returnSwiftCodes;
        parent::__construct($context, $data);
    }

    /**
     * Render the grid cell value
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $returnReasonCode = $row->getReturnSwiftCode();
        if ($returnReasonCode != '') {
            $swiftCodes = $this->returnSwiftCodes->toOptionArray();
            $keys = array_column($swiftCodes, 'value');
            $position = array_search($returnReasonCode, $keys);
            return (string)$swiftCodes[$position]['label'];
        } else {
            return '';
        }
    }
}
