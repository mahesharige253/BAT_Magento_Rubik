<?php

namespace Bat\NewProduct\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * @class Actions
 * Column Action Edit/Delete
 */
class Actions extends Column
{

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface       $urlBuilder
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['delete'] = [
                'href' => $this->_urlBuilder->getUrl(
                    'newproduct/newproduct/delete',
                    ['entity_id' => $item['entity_id']]
                ),
                'label' => __('Remove'),
                'hidden' => false,
                    'confirm' => [
                        'title' => __(
                            'Remove Product'
                        ),
                        'message' => __(
                            'Are you sure you wan\'t to remove product %1 ?',
                            $item['product_name']
                        )
                    ]
                ];
            }
        }
         return $dataSource;
    }
}
