<?php

namespace Bat\Information\Model\ResourceModel;

class InformationBrandForm extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    
    /**
     * Initialize construct Bat\Information\Model\ResourceModel\InformationBrandForm
     */
    public function _construct()
    {
        $this->_init('bat_information_brand', 'id');
    }//end _construct()
}//end class


