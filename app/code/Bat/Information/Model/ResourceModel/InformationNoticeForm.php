<?php

namespace Bat\Information\Model\ResourceModel;

class InformationNoticeForm extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @var fieldname
     */
    protected $_idFieldName = 'id';
    
    /**
     * Initialize construct Bat\Information\Model\ResourceModel\InformationNoticeForm
     */
    public function _construct()
    {
        $this->_init('bat_information_notice', 'id');
    }//end _construct()
}//end class

