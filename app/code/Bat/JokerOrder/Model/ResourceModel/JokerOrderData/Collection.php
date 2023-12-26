<?php

namespace Bat\JokerOrder\Model\ResourceModel\JokerOrderData;

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
            \Bat\JokerOrder\Model\JokerOrderData::class,
            \Bat\JokerOrder\Model\ResourceModel\JokerOrderData::class
        );
    } //end _construct()
} //end class
