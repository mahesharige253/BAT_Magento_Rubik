<?php

namespace Bat\Information\Block\Adminhtml\Information\EditOrderManual\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\App\RequestInterface;

class Info extends Generic implements TabInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $_coreSession;

    /**
     * @var Config
     */
    private $_wysiwygConfig;

    /**
     * @var RequestInterface
     */
    private $requestInterface;


    /**
     * Info constructor.
     *
     * @param Context                                            $context
     * @param Registry                                           $registry
     * @param FormFactory                                        $formFactory
     * @param SessionManagerInterface                            $coreSession
     * @param Config                                             $wysiwygConfig
     * @param array                                              $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        SessionManagerInterface $coreSession,
        Config $wysiwygConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_coreSession = $coreSession;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepareform function
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('informationformOrdermanual');
        $form = $this->_formFactory->create();
        $val = '';

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Order Manual Information')]
        );
        if ($model->getId()) {
            $fieldset->addField(
                'id',
                'hidden',
                ['name' => 'id']
            );
        }

        $fieldset->addField(
            'information_title',
            'text',
            [
                'name' => 'information_title',
                'label' => __('Order Manual'),
                'comment' => __('Order Manual'),
                'required' => true,
            ]
        );


        $fieldset->addField(
            'enable_link',
            'select',
            [
                'name' => 'enable_link',
                'label' => __('Status'),
                'comment' => __('Status'),
                'values' => [
                    [
                        'value' => 'Enabled',
                        'label' => 'Enabled',
                    ],
                    [
                        'value' => 'Disabled',
                        'label' => 'Disabled',
                    ],
                ],
            ]
        );

        $fieldset->addField(
            'orderpdf',
            'file',
            [
                'name' => 'orderpdf',
                'label' => __('Upload PDF'),
                'comment' => __('Upload PDF'),
                'value' => $model->getData('orderpdf'),
                'note' => $model->getData('orderpdf') == '' ? '' : $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $model->getData('orderpdf'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'ordermanualbanner',
            'image',
            [
                'name' => 'ordermanualbanner',
                'label' => __('Banner Image'),
                'comment' => __('Banner Image'),
                'required' => false,
            ]
        );

        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    } //end _prepareForm()

    /**
     * GetTabLabel function

     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('InformationForm');
    } //end getTabLabel()

    /**
     * GetTabTitle function

     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('InformationForm');
    } //end getTabTitle()

    /**
     * CanshowTab function

     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    } //end canShowTab()

    /**
     * Ishidden Function

     * @return boolean
     */
    public function isHidden()
    {
        return false;
    } //end isHidden()
} //end class
