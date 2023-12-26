<?php

namespace Bat\RequisitionList\Ui\Component\Listing\Column;

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
                $item[$this->getData('name')]['edit'] = [
                    'href' => $this->_urlBuilder->getUrl(
                        'requisitionlist/requisitionlist/edit',
                        ['entity_id' => $item['entity_id']]
                    ),
                    'label' => __('Edit'),
                    'hidden' => false
                ];
                $item[$this->getData('name')]['delete'] = [
                'href' => $this->_urlBuilder->getUrl(
                    'requisitionlist/requisitionlist/delete',
                    ['entity_id' => $item['entity_id']]
                ),
                'label' => __('Delete'),
                'hidden' => false,
                    'confirm' => [
                        'title' => __(
                            'Delete Requisitionlist'
                        ),
                        'message' => __(
                            'Are you sure you want to delete Requisitionlist %1 ?',
                            $item['name']
                        )
                    ]
                ];
            }
        }
         return $dataSource;
    }
}
