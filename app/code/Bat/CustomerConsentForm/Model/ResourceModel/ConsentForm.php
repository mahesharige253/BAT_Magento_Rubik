<?php

namespace Bat\CustomerConsentForm\Model\ResourceModel;

class ConsentForm extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    
    /**
     * Initialize construct Bat\CustomerConsentForm\Model\ResourceModel\ConsentForm
     */
    public function _construct()
    {
        $this->_init('customer_consent_forms', 'id');
    }//end _construct()
}//end class
