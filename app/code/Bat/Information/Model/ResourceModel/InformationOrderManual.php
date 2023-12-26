<?php

namespace Bat\Information\Model\ResourceModel;

class InformationOrderManual extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    
    /**
     * Initialize construct Bat\Information\Model\ResourceModel\InformationOrderManual
     */
    public function _construct()
    {
        $this->_init('bat_information_order_manual', 'id');
    }//end _construct()
}//end class


