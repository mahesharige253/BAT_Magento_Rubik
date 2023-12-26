<?php

namespace Bat\Information\Model\ResourceModel\InformationFaqForm;

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
            \Bat\Information\Model\InformationFaqForm::class,
            \Bat\Information\Model\ResourceModel\InformationFaqForm::class
        );
    } //end _construct()
} //end class

