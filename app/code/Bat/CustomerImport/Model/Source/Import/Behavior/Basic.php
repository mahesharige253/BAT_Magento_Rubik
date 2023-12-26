<?php

namespace Bat\CustomerImport\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;

use Magento\ImportExport\Model\Source\Import\Behavior\Basic as BehaviorBasic;

/**
 * Import behavior source model used for defining the behaviour during the import.
 *
 * @api
 * @since 100.0.2
 */
class Basic extends BehaviorBasic
{
    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add/Update')
        ];
    }
}
