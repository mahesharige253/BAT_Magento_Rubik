<?php
namespace Bat\NpiProduct\Block\Adminhtml\NpiProduct\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\System\Store;

/**
 * @class Form
 * Prepare form for updating product tags
 */
class Form extends Generic
{
    /**
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var Registry
     */
    protected $_coreRegistry;
    /**
     * @var SecureHtmlRenderer|mixed
     */
    private mixed $secureRenderer;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->_systemStore = $systemStore;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $registry, $formFactory, $data);
        $this->secureRenderer = $secureRenderer ?? ObjectManager::getInstance()->get(SecureHtmlRenderer::class);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('update_form');
        $this->setTitle(__('Update Product Tags'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'enctype' => 'multipart/form-data',
            'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setHtmlIdPrefix('npiproduct_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => 'Product Tag: ', 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'new_product_tag',
            'checkbox',
            [
                'name' => 'new_product_tag',
                'label' => __('New Product Tag'),
                'title' => __('New Product Tag'),
                'value' => 1
            ]
        );

        $fieldset->addField(
            'change_new_product_tag',
            'checkbox',
            [
                'name' => 'change_new_product_tag',
                'label' => __('Change'),
                'title' => __('Change'),
                'value' => 1
            ]
        );

        $fieldset->addField(
            'limited_product_tag',
            'checkbox',
            [
                'name' => 'limited_product_tag',
                'label' => __('Limited Product Tag'),
                'title' => __('Limited Product Tag'),
                'value' => 1
            ]
        );

        $fieldset->addField(
            'change_limited_product_tag',
            'checkbox',
            [
                'name' => 'change_limited_product_tag',
                'label' => __('Change'),
                'title' => __('Change'),
                'value' => 1
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
