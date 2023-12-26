<?php

namespace Bat\Information\Model\ResourceModel\InformationForm;

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
            \Bat\Information\Model\InformationForm::class,
            \Bat\Information\Model\ResourceModel\InformationForm::class
        );
    } //end _construct()
} //end class
