<?php

namespace Bat\BestSellers\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\App\ResourceConnection;

class GetBestSellers extends AbstractModel
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * PriceMaster Update Construct
     *
     * @param ResourceConnection $resourceConnection
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get bestsellers products
     *
     * @param string $sigunguCode
     * @param string $limit
     * @return array
     */
    public function getBestSellers($sigunguCode, $limit = 0)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bat_bestseller_products');
        $select = $connection->select()
        ->from(
            ['c' => $tableName],
            ['product_ids']
        )->where(
            'c.sigungu_code = ?',
            $sigunguCode
        );
        $bestsellers = $connection->fetchOne($select);
        $productIds = [];
        if ($bestsellers != '') {
            $productIds = explode(",", $bestsellers);
            if ($limit > 0) {
                $productIds = array_slice($productIds, 0, $limit);
            }
        }
        return $productIds;
    }

    /**
     * Get bestsellers products
     *
     * @param string $sigunguCode
     * @return array
     */
    public function getBestSellersRl($sigunguCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bat_bestseller_products_rl');
        $select = $connection->select()
        ->from(
            ['c' => $tableName],
            ['product_ids']
        )->where(
            'c.sigungu_code = ?',
            $sigunguCode
        );
        $bestsellers = $connection->fetchOne($select);
        $productIds = [];
        if ($bestsellers != '') {
            $productIds = explode(",", $bestsellers);
        }
        return $productIds;
    }

    /**
     * Check if product is bestseller for particular sigungu code
     *
     * @param string $sigunguCode
     * @param array $productId
     * @return bool
     */
    public function isProductBestSellers($sigunguCode, $productId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bat_bestseller_products');
        $select = $connection->select()
        ->from(
            ['c' => $tableName],
            ['product_ids'],
        )->where(
            'c.sigungu_code = ?',
            $sigunguCode
        );
        $result = $connection->fetchOne($select);
        if ($result != '') {
            $presentBestsellerId = explode(',', $result);
            if (in_array($productId, $presentBestsellerId)) {
                return true;
            }
        }
        return false;
    }
}
