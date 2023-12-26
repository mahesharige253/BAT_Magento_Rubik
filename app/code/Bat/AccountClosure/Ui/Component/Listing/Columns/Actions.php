<?php

namespace Bat\AccountClosure\Ui\Component\Listing\Columns;

class Actions extends \Magento\Ui\Component\Listing\Columns\Column
{
    public const URL_PATH_STORE_EDIT   = 'accountclosure/accountclosure/edit';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Actions constructor.
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory           $uiComponentFactory
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param array                                                        $components
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }//end __construct()

    /**
     * PrepareDataSource function

     * @param array $dataSource
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['customer_id'])) {
                    $item[$this->getData('name')] = [
                        'edit'   => [
                            'href'  => $this->urlBuilder->getUrl(
                                static::URL_PATH_STORE_EDIT,
                                [
                                    'id' => $item['customer_id'],
                                ]
                            ),
                            'label' => __('Edit'),
                        ]
                    ];
                }//end if
            }//end foreach
        }//end if

        return $dataSource;
    }//end prepareDataSource()
}//end class


