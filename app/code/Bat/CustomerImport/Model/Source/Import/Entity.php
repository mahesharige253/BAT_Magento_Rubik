<?php

namespace Bat\CustomerImport\Model\Source\Import;

use Magento\ImportExport\Model\Source\Import\Entity as ImportEntity;

/**
 * Source import entity model
 *
 * @api
 * @since 100.0.2
 */
class Entity extends ImportEntity
{
    /**
     * {{@inheritdoc}}
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => __('-- Please Select --'), 'value' => ''];
        foreach ($this->_importConfig->getEntities() as $entityName => $entityConfig) {
            if (!in_array(
                $entityName,
                ['advanced_pricing','catalog_product','customer_finance', 'stock_sources', 'customer_address']
            )
            ) {
                $options[] = ['label' => __($entityConfig['label']), 'value' => $entityName];
            }
        }
        return $options;
    }
}
