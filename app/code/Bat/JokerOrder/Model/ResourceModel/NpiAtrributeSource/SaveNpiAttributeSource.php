<?php
declare(strict_types=1);

namespace Bat\JokerOrder\Model\ResourceModel\NpiAtrributeSource;

use Magento\Framework\App\ResourceConnection;

/**
 * Save Shipment Source
 */
class SaveNpiAttributeSource
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Update NPI joker order
     *
     * @param array $data
     * @return void
     */
    public function execute(array $data)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('customer_entity_datetime');
        array_chunk($data, 200);
        $connection->insertOnDuplicate($tableName, $data);
    }
}
