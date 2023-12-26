<?php

namespace Bat\Customer\Model;

class ChangeAddress extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize construct Bat\Customer\Model\ChangeAddress
     */
    public function _construct()
    {
        $this->_init(\Bat\Customer\Model\ResourceModel\ChangeAddress::class);
    }//end _construct()
}//end class
