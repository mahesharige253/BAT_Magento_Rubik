<?php
namespace Bat\NpiProduct\Block\Adminhtml\NpiProduct;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Registry;

/**
 * @class Edit
 * Initialize block for product tag update page
 */
class Edit extends Container
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize Product Tag update page block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Bat_NpiProduct';
        $this->_controller = 'adminhtml_npiProduct';
        parent::_construct();
        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->addButton(
            'back',
            [
                'label' => __('Back'),
                'onclick' => 'setLocation(\'' . $this->getUrl('catalog/product') . '\')',
                'class' => 'back'
            ],
            -1
        );
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        return __('NPI Products');
    }

    /**
     * Get form action URL.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        if ($this->hasFormActionUrl()) {
            return $this->getData('form_action_url');
        }
        return $this->getUrl('*/*/save');
    }
}
