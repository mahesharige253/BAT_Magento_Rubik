<?php

namespace Bat\Information\Model;

class InformationNoticeForm extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\Information\Model\InformationNoticeForm
     */
    public function _construct()
    {
        $this->_init(\Bat\Information\Model\ResourceModel\InformationNoticeForm::class);
    }//end _construct()
}//end class

