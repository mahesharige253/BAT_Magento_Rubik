<?php

namespace Bat\Information\Model\ResourceModel;

class InformationForm extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    
    /**
     * Initialize construct Bat\Information\Model\ResourceModel\InformationForm
     */
    public function _construct()
    {
        $this->_init('bat_information_barcode', 'id');
    }//end _construct()
}//end class
