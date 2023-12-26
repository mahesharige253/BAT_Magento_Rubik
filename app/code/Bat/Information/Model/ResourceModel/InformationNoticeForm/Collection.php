<?php

namespace Bat\Information\Model\ResourceModel\InformationNoticeForm;

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
            \Bat\Information\Model\InformationNoticeForm::class,
            \Bat\Information\Model\ResourceModel\InformationNoticeForm::class
        );
    } //end _construct()
} //end class

