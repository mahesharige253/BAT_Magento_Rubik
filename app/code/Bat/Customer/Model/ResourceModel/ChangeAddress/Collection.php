<?php

namespace Bat\Customer\Model\ResourceModel\ChangeAddress;

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
            \Bat\Customer\Model\ChangeAddress::class,
            \Bat\Customer\Model\ResourceModel\ChangeAddress::class
        );
    } //end _construct()
} //end class
