<?php

namespace Bat\ShipmentUpdate\Model\ResourceModel\EdaOrderFailed;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'entity_id';
    /**
     * Initialize construct
     *
     * Initialize CustomerConsentForms
     */
    public function _construct()
    {
        $this->_init(
            \Bat\ShipmentUpdate\Model\EdaOrderFailed::class,
            \Bat\ShipmentUpdate\Model\ResourceModel\EdaOrderFailed::class
        );
    } //end _construct()
} //end class
