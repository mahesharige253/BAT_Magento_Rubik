<?php

namespace Bat\RequisitionList\Plugin;

use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\RequisitionListRepository;

class CreateDuplicateRl
{
    /**
     * @inheritdoc
     */
    public function beforeSave(
        RequisitionListRepository $subject,
        RequisitionListInterface $requisitionList,
        $processName = false
    ) {
        return [$requisitionList, $processName = false];
    }
}
