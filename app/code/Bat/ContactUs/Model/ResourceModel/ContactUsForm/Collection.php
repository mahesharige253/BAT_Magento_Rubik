<?php

namespace Bat\ContactUs\Model\ResourceModel\ContactUsForm;

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
            \Bat\ContactUs\Model\ContactUsForm::class,
            \Bat\ContactUs\Model\ResourceModel\ContactUsForm::class
        );
    } //end _construct()
} //end class
