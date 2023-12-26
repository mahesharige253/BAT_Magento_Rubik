<?php

namespace Bat\Rma\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\UrlInterface;

/**
 * @class View
 * Get Order View Url
 */
class View extends Column
{

    /**
     * @var UrlInterface
     */
    private UrlInterface $url;

    /**
     * @var OrderInterface
     */
    private OrderInterface $orderInterface;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderInterface $orderInterface
     * @param UrlInterface $url
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderInterface $orderInterface,
        UrlInterface $url,
        array $components = [],
        array $data = []
    ) {
        $this->orderInterface = $orderInterface;
        $this->url = $url;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare data source
     *
     * @param array $dataSource
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                try {
                    $order = $this->orderInterface->loadByIncrementId($item['order_id']);
                    $orderViewUrl = $this->url->getUrl(
                        'sales/order/view',
                        ['order_id' => $order->getEntityId()]
                    );
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $orderViewUrl,
                        'label' => __('View'),
                        'hidden' => false,
                        'target' => '_blank'
                    ];
                } catch (\Exception $e) {
                    $item['order_id'] = 'Order Not Found';
                }
            }
        }
        return $dataSource;
    }
}
