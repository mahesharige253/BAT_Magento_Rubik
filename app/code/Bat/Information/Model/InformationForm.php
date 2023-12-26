<?php

namespace Bat\Information\Model;

class InformationForm extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\Information\Model\InformationForm
     */
    public function _construct()
    {
        $this->_init(\Bat\Information\Model\ResourceModel\InformationForm::class);
    }//end _construct()
}//end class
