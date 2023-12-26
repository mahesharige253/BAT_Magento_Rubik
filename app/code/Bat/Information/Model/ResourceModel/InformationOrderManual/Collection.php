<?php

namespace Bat\Information\Model\ResourceModel\InformationOrderManual;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    /**
     * Initialize construct
     *
     * Initialize CustomerConsentForms
     */
    public function _construct()
    {
        $this->_init(
            \Bat\Information\Model\InformationOrderManual::class,
            \Bat\Information\Model\ResourceModel\InformationOrderManual::class
        );
    } //end _construct()
} //end class


