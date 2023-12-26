<?php

namespace Bat\Information\Block\Adminhtml\Information\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Cms\Model\Wysiwyg\Config;
use Bat\Information\Model\InformationBrandFormFactory;
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
        InformationBrandFormFactory $informationBrandFormFactory,
        array $data = []
    ) {
        $this->_coreSession = $coreSession;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->informationBrandFormFactory = $informationBrandFormFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepareform function
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('informationform');
        $form = $this->_formFactory->create();
        $val = '';
        $brandtype = [];
        $collection = $this->informationBrandFormFactory->create()->getCollection();
        $collection->addFieldToFilter('enable_link', 'Enabled');
        $records = $collection->getData();
        if (count($records) > 0) {
            foreach ($records as $data) {
            $brandtype[$data['id']] = $data['information_title'];
            }
        }
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Product Barcode')]
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
                'label' => __('Barcode Name'),
                'comment' => __('Barcode Name'),
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
                        'label' => __('Enabled'),
                    ],
                    [
                        'value' => 'Disabled',
                        'label' => __('Disabled'),
                    ],
                ],
            ]
        );

        $fieldset->addField(
            'consumer_price',
            'text',
            [
                'name' => 'consumer_price',
                'label' => __('Consumer Price'),
                'comment' => __('Consumer Price'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'productimage',
            'image',
            [
                'name' => 'productimage',
                'label' => __('Product Image'),
                'comment' => __('Product Image'),
                'note' => __('The Product image height and width should be 500*500'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'packbarcode',
            'image',
            [
                'name' => 'packbarcode',
                'label' => __('Pack Barcode Image'),
                'comment' => __('Pack Barcode Image'),
                'note' => __('The Pack Barcode image height and width should be 200*90'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'cartonbarcode',
            'image',
            [
                'name' => 'cartonbarcode',
                'label' => __('Carton Barcode Image'),
                'comment' => __('Carton Barcode Image'),
                'note' => __('The Carton Barcode Image height and width should be 200*90'),
                'required' => false,
            ]
        );

        $fieldset->addField(
            'brand_type',
            'select',
            [
                'name' => 'brand_type',
                'label' => __('Brand Name'),
                'comment' => __('Brand Name'),
                'values' => $brandtype
            ]
        );

        $fieldset->addField(
            'position',
            'text',
            [
                'name' => 'position',
                'label' => __('Position'),
                'comment' => __('Position'),
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
