<?php

namespace Bat\Information\Block\Adminhtml\Information;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class Editfaq extends Container
{

    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * Edit constructor.
     *
     * @param Context  $context
     * @param Registry $registry
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }//end __construct()

    /**
     * Intialize constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId   = 'id';
        $this->_controller = 'adminhtml_information';
        $this->_blockGroup = 'Bat_Information';

        parent::_construct();
        $this->buttonList->update('save', 'label', __('Save'));
    }//end _construct()

    /**
     * PrepareLayout Function

     * @return Container
     */
    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('post_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'post_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'post_content');
                }
            };
        ";
        $this->removeButton('reset');
        return parent::_prepareLayout();
    }//end _prepareLayout()
}//end class
