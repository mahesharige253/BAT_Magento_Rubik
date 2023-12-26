<?php

namespace Navigate\BannerSlider\Ui\Component\Listing\Columns\Bannerslider;

class Actions extends \Magento\Ui\Component\Listing\Columns\Column
{
    private const URL_PATH_STORE_EDIT = 'bannerslider/bannerslider/edit';
    private const URL_PATH_STORE_DELETE = 'bannerslider/bannerslider/delete';

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
    } //end __construct()

    /**
     * Function PrepareDatasource

     * @param array $dataSource
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['id'])) {
                    $id = $item['title'];
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_STORE_EDIT,
                                [
                                    'id' => $item['id'],
                                ]
                            ),
                            'label' => __('Edit'),
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_STORE_DELETE,
                                [
                                    'id' => $item['id'],
                                ]
                            ),
                            'label' => __('Remove'),
                            'confirm' => [
                                'title' => __('Delete "' . $id . '"'),
                                'message' => __('Are you sure wan\'t to delete "' . $id . '" ?'),
                            ],
                        ],
                    ];
                } //end if
            } //end foreach
        } //end if
        return $dataSource;
    } //end prepareDataSource()
} //end class
