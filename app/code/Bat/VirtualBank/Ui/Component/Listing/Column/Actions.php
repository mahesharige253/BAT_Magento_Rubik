<?php

namespace Bat\VirtualBank\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Framework\AuthorizationInterface;

/**
 * @class Actions
 * Column Action Edit/Delete
 */
class Actions extends Column
{
    protected const ACL_RESOURCE = 'Bat_VirtualBank::delete';

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var AuthorizationInterface
     */
    private AuthorizationInterface $authorization;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->authorization = $authorization;
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
                        'vba/virtualbank/edit',
                        ['bank_id' => $item['bank_id']]
                    ),
                    'label' => __('Edit'),
                    'hidden' => false
                ];
                if ($this->authorization->isAllowed(self::ACL_RESOURCE)) {
                    $item[$this->getData('name')]['delete'] = [
                        'href' => $this->_urlBuilder->getUrl(
                            'vba/virtualbank/delete',
                            ['bank_id' => $item['bank_id']]
                        ),
                        'label' => __('Delete'),
                        'hidden' => false,
                        'confirm' => [
                            'title' => __(
                                'Delete Bank'
                            ),
                            'message' => __(
                                'Are you sure you want to delete Bank %1 ?',
                                $item['bank_name']
                            )
                        ]
                    ];
                }
            }
        }
         return $dataSource;
    }
}
