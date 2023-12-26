<?php

namespace Bat\CustomerConsentForm\Model;

class ConsentForm extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\CustomerConsentForm\Model\ConsentForm
     */
    public function _construct()
    {
        $this->_init(\Bat\CustomerConsentForm\Model\ResourceModel\ConsentForm::class);
    }//end _construct()
}//end class
