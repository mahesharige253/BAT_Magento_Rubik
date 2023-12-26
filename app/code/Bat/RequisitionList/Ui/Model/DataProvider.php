<?php
namespace Bat\RequisitionList\Ui\Model;

use Bat\RequisitionList\Model\ResourceModel\RequisitionListAdmin\CollectionFactory;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var array
     */
    protected $loadedData;
    
    /**
     * @var CollectionFactory
     */
    protected $requisitionlistCollectionFactory;
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $requisitionlistCollectionFactory
     * @param array $meta
     * @param array $data
     */

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $requisitionlistCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $requisitionlistCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
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
