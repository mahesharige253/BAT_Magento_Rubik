<?php

namespace Bat\CustomerImport\Block\Adminhtml\CustomerImport;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;

/**
 * Import customers
 */
class Customer extends Container
{
    /**
     * Registry.
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Form Initialization
     */
    protected function _construct()
    {
        $this->_objectId = 'customer_id';
        $this->_blockGroup = 'Bat_CustomerImport';
        $this->_controller = 'adminhtml_customerImport';
        parent::_construct();
        $this->buttonList->remove('back');
        $this->buttonList->update('save', 'label', __('Import'));
        $this->buttonList->remove('reset');
    }

    /**
     * Retrieve text for header element depending on loaded image.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Import Customer Accounts');
    }

    /**
     * Check permission for passed action.
     *
     * @param string $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
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
        return $this->getUrl('*/*/importpost');
    }
}
