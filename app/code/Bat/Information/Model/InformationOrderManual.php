<?php

namespace Bat\Information\Model;

class InformationOrderManual extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\Information\Model\InformationOrderManual
     */
    public function _construct()
    {
        $this->_init(\Bat\Information\Model\ResourceModel\InformationOrderManual::class);
    }//end _construct()
}//end class


