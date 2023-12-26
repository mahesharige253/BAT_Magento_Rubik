<?php
namespace Bat\JokerOrder\Block\Adminhtml\Jokerorder;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

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
     * Initialize cms page edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'entity_id';
        $this->_blockGroup = 'Bat_JokerOrder';
        $this->_controller = 'adminhtml_jokerorder';

        parent::_construct();

        if ($this->_isAllowedAction('Bat_JokerOrder::jokerorder')) {
            $this->buttonList->update('save', 'label', __('Save'));
            
        } else {
            $this->buttonList->remove('save');
        }
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        return __('Joker Order NPI/E-call');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Get save and continue url
     *
     * @return string
     */
    public function _getSaveAndContinueUrl()
    {
        return $this->getUrl(
            'jokerorder/*/save',
            ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']
        );
    }
}
