<?php

namespace Bat\NewProduct\Block\Adminhtml\NewProduct\Edit\Tab;

use Bat\NewProduct\Model\NewProductModelFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Registry;

/**
 * @class Products
 *
 * Product  List tab
 */
class Products extends Extended
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var  Registry
     */
    protected $registry;

    /**
     * @var NewProductModelFactory
     */
    private NewProductModelFactory $newProductModelFactory;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param Registry $registry
     * @param NewProductModelFactory $newProductModelFactory
     * @param CollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Registry $registry,
        NewProductModelFactory $newProductModelFactory,
        CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->newProductModelFactory = $newProductModelFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->registry = $registry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * _construct
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productsGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('entity_id')) {
            $this->setDefaultFilter(['in_product' => 1]);
        }
    }

    /**
     * Add Column Filter To Collection
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this|Products
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_product') {
            $productIds = $this->_getSelectedProducts();

            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Prepare collection
     */
    protected function _prepareCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('price');
        $collection->addAttributeToSelect('status');
        $collection->addAttributeToFilter('pricetag_type', ['nin' => [1,2,3]]);
        $collection->addAttributeToFilter('is_plp', ['eq' => [1]]);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_product',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_product',
                'align' => 'center',
                'index' => 'entity_id',
                'values' => $this->_getSelectedProducts(),
                'required' => true,
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('Product ID'),
                'type' => 'number',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'required' => true,
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'class' => 'xxx',
                'width' => '50px',
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('Sku'),
                'index' => 'sku',
                'class' => 'xxx',
                'width' => '50px',
            ]
        );
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'index' => 'price',
                'width' => '50px',
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'type' => 'options',
                'index' => 'status',
                'width' => '50px',
                'options' => [
                    '1' => __('Enabled'),
                    '2' => __('Disabled')
                ]
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/productsgrid', ['_current' => true]);
    }

    /**
     * Get Row Url
     *
     * @param  object $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return '';
    }

    /**
     * Get selected products
     *
     * @return array
     */
    protected function _getSelectedProducts()
    {
        $newProduct = $this->getNewProducts();
        return $newProduct->getProducts();
    }

    /**
     * Retrieve selected products
     *
     * @return array
     */
    public function getSelectedProducts()
    {
        $newProduct = $this->getNewProducts();
        $selected = $newProduct->getProducts();

        if (!is_array($selected)) {
            $selected = [];
        }
        return $selected;
    }

    /**
     * New Products Model
     *
     * @return \Bat\NewProduct\Model\NewProductModel
     */
    protected function getNewProducts()
    {
        return $this->newProductModelFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return true;
    }
}
