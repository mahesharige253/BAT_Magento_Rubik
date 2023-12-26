<?php

namespace Bat\ContactUs\Model\ResourceModel;

class ContactUsForm extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    
    /**
     * Initialize construct Bat\ContactUs\Model\ResourceModel\ContactUsForm
     */
    public function _construct()
    {
        $this->_init('contactus_data', 'id');
    }//end _construct()
}//end class
