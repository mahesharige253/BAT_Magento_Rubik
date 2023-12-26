<?php
namespace Bat\JokerOrder\Block\Adminhtml\Jokerorder\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('edit_form');
        $this->setTitle(__('Customer Attribute Update'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
       //Preparing the form here.
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'enctype' => 'multipart/form-data',
            'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setHtmlIdPrefix('jokerorder_');
        $actionName = $this->getRequest()->getActionName();

        if ($actionName == 'ecall') {
            $headerText = 'e-call';
        } else {
            $headerText = 'NPI';
        }

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => 'Joker Order: '.$headerText, 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'joker_order_type',
            'hidden',
            ['name' => 'joker_order_type', 'label' => __('joker order type'),
            'title' => __('joker order type'), 'value' => $actionName]
        );

        $fieldset->addField(
            'start_date',
            Date::class,
            ['name' => 'start_date', 'label' => __('Start Date'),
            'title' => __('Joker Order Start Date'), 'required' => true,
            'date_format' => 'yyyy-MM-dd']
        );

        $fieldset->addField(
            'end_date',
            Date::class,
            ['name' => 'end_date', 'label' => __('End Date'),
            'title' => __('Joker Order End Date'), 'required' => true,
            'date_format' => 'yyyy-MM-dd']
        );
        
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
