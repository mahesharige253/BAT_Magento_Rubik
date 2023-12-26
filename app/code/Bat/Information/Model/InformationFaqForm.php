<?php

namespace Bat\Information\Model;

class InformationFaqForm extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\Information\Model\InformationFaqForm
     */
    public function _construct()
    {
        $this->_init(\Bat\Information\Model\ResourceModel\InformationFaqForm::class);
    }//end _construct()
}//end class
