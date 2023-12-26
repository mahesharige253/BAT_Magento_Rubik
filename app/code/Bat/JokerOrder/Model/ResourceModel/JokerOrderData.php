<?php

namespace Bat\JokerOrder\Model\ResourceModel;

class JokerOrderData extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'entity_id';
    
    /**
     * Initialize construct Bat\JokerOrder\Model\ResourceModel\JokerOrderData
     */
    public function _construct()
    {
        $this->_init('bat_joker_order_frequency', 'entity_id');
    }//end _construct()
}//end class
