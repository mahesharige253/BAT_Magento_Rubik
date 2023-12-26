<?php
namespace Bat\VirtualBank\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Bat\VirtualBank\Model\ResourceModel\AccountResource\CollectionFactory as AccountCollectionFactory;

class RemainingAccount extends Column
{
    /**
     * @var Escaper
     */
    protected Escaper $escaper;

    /**
     * @var AccountCollectionFactory
     */
    private AccountCollectionFactory $accountCollectionFactory;

    /**
     * RemainingAccount constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param AccountCollectionFactory $accountCollectionFactory
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        AccountCollectionFactory $accountCollectionFactory,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->accountCollectionFactory = $accountCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $bankCode = $item['bank_code'];
                $item[$this->getData('name')] = $this->getRemainingAccountCount($bankCode);
            }
        }
        return $dataSource;
    }

    /**
     * Get Remaining Account Count
     *
     * @param string $bankCode
     * @return integer
     */
    public function getRemainingAccountCount($bankCode)
    {
        $virtualAccounts = $this->accountCollectionFactory->create()
            ->addFieldToFilter('bank_code', ['eq' => $bankCode])
            ->addFieldToFilter('vba_assigned_status', ['eq' => 0]);
        return $virtualAccounts->getSize();
    }
}
