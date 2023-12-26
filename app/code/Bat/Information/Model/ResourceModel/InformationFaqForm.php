<?php

namespace Bat\Information\Model\ResourceModel;

class InformationFaqForm extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    
    /**
     * Initialize construct Bat\Information\Model\ResourceModel\InformationFaqForm
     */
    public function _construct()
    {
        $this->_init('bat_information_faq', 'id');
    }//end _construct()
}//end class

