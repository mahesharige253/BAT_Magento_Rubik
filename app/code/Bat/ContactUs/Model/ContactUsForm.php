<?php

namespace Bat\ContactUs\Model;

class ContactUsForm extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\ContactUs\Model\ContactUsForm
     */
    public function _construct()
    {
        $this->_init(\Bat\ContactUs\Model\ResourceModel\ContactUsForm::class);
    }//end _construct()
}//end class
