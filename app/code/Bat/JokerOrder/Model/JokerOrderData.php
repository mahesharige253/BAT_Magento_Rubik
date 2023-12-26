<?php

namespace Bat\JokerOrder\Model;

class JokerOrderData extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\JokerOrder\Model\JokerOrderData
     */
    public function _construct()
    {
        $this->_init(\Bat\JokerOrder\Model\ResourceModel\JokerOrderData::class);
    }//end _construct()
}//end class
