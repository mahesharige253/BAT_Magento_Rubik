<?php

namespace Bat\CustomerConsentForm\Model\ResourceModel\ConsentForm;

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
            \Bat\CustomerConsentForm\Model\ConsentForm::class,
            \Bat\CustomerConsentForm\Model\ResourceModel\ConsentForm::class
        );
    } //end _construct()
} //end class
