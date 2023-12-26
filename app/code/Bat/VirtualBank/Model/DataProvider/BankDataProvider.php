<?php

namespace Bat\VirtualBank\Model\DataProvider;

use Bat\VirtualBank\Model\ResourceModel\BankResource\BankCollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * @class BankDataProvider
 *
 * Return Loaded data for bank edit
 */
class BankDataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param BankCollectionFactory $bankCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        BankCollectionFactory $bankCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $bankCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Return loaded data for edit
     *
     * @return array
     */
    public function getData()
    {
        $items = $this->collection->getItems();
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }
        return $this->loadedData;
    }
}
