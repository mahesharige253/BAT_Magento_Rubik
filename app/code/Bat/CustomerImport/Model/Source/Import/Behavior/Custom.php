<?php

namespace Bat\CustomerImport\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Source\Import\Behavior\Custom as CustomBehavior;

class Custom extends CustomBehavior
{
    /**
     * {{@inheritdoc}}
     */
    public function toArray()
    {
        return [
            \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE => __('Add/Update Complex Data')
        ];
    }
}
