<?php

namespace Bat\BestSellers\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Bat\Customer\Model\ResourceModel\SigunguCodeResource\CollectionFactory as SigunguCodeCollectionFactory;

class PrepareBestSellers extends AbstractModel
{
    public const TOTAL_BESTSELLER_EXCLUDING_NPI = 5;
    public const NO_SIGUNGUCODE = '999999';

    /**
     * @var BestSellersFactory
     */
    private $bestsellersFactory;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var SigunguCodeCollectionFactory
     */
    private $sigunguCollectionFactory;

    /**
     * PriceMaster Update Construct
     *
     * @param BestSellersFactory $bestsellersFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezoneInterface
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param SigunguCodeCollectionFactory $sigunguCollectionFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        BestSellersFactory $bestsellersFactory,
        ProductCollectionFactory $productCollectionFactory,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface,
        CustomerCollectionFactory $customerCollectionFactory,
        SigunguCodeCollectionFactory $sigunguCollectionFactory
    ) {
        $this->bestsellersFactory = $bestsellersFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->timezoneInterface = $timezoneInterface;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->sigunguCollectionFactory = $sigunguCollectionFactory;
    }

    /**
     * Generate bestsellers data.
     *
     * @return void
     */
    public function generateBestSellers()
    {
        $sigunguCodes = $this->getSigunguCodes();
        foreach ($sigunguCodes as $sigunguCode) {
            $this->process($sigunguCode);
            $this->processRl($sigunguCode);
        }
    }

    /**
     * Ger sigungu codes available in the bat_bestsellers table
     *
     * @return array
     */
    private function getSigunguCodes()
    {
        $sigunguCodes = $this->sigunguCollectionFactory->create();
        $sigunguCodes->addFieldToSelect('sigungu_code');
        $sigunguCodes->getSelect()->distinct(true);
        
        $codes = [];
        foreach ($sigunguCodes as $sigunguCode) {
            $codes[] = $sigunguCode->getSigunguCode();
        }
       
        //If bestseller products not available then it will return from this one.
        $codes[] = self::NO_SIGUNGUCODE;

        return $codes;
    }

    /**
     * Calculate bestseller products from bestsellers table and insert into bestsellers cron table.
     *
     * @param string $sigunguCode
     * @return void
     */
    public function process($sigunguCode)
    {
        //Get top 5 bestseller products.
        $bestsellerIds = $this->getBestsellerProducts($sigunguCode);

        //Get NPI Products
        $npiProducts = $this->getNpiProducts();

        $npiBestsellerIds = array_intersect($bestsellerIds, $npiProducts);

        $bestsellerForPlp = self::TOTAL_BESTSELLER_EXCLUDING_NPI + count($npiBestsellerIds);
        $excludeIds = array_merge($bestsellerIds, $npiProducts);

        //If any bestseller is part of NPI then add more bestsellers.
        if (count($npiBestsellerIds) > 0) {
            $remainingBestsellerIds = $this->getBestsellerProducts($sigunguCode, count($npiBestsellerIds), $excludeIds);
            $bestsellerIds = array_merge($bestsellerIds, $remainingBestsellerIds);
        }

        //If bestsellers product count is not enough of bestsellers + bestseller in npi
        if (count($bestsellerIds) < $bestsellerForPlp) {
            $remainingCount = $bestsellerForPlp - count($bestsellerIds);
            $commonIds = $this->getCommonBestsellerProducts($excludeIds, $remainingCount);
            $bestsellerIds = array_merge($bestsellerIds, $commonIds);
        }

        $bestsellerProductIds = implode(",", $bestsellerIds);

        //insert to custom table.
        $this->createUpdateRecord($sigunguCode, $bestsellerProductIds);
    }

    /**
     * Calculate bestseller products from bestsellers table and insert into bestsellers cron table.
     *
     * @param string $sigunguCode
     * @return void
     */
    public function processRl($sigunguCode)
    {
        $count = $this->scopeConfig->getValue("best_sellers/general/best_seller_rlproduct_limit");
        //Get top 5 bestseller products.
        $bestsellerIds = $this->getBestsellerProductsRl($sigunguCode, $count);
        
        //If bestsellers product count is not enough of bestsellers + bestseller in npi
        if (count($bestsellerIds) < $count) {
            $remainingCount = $count - count($bestsellerIds);
            $commonIds = $this->getCommonBestsellerProductsRl($bestsellerIds, $remainingCount);
            $bestsellerIds = array_merge($bestsellerIds, $commonIds);
        }

        $bestsellerProductIds = implode(",", $bestsellerIds);

        //insert to custom table.
        $this->createUpdateRlRecord($sigunguCode, $bestsellerProductIds);
    }

    /**
     * Get NPI products
     *
     * @return array
     */
    private function getNpiProducts()
    {
        $npiProductIds = [];
        $newProductCollectionData = $this->productCollectionFactory->create();
        $newProductCollectionData->addFieldToSelect('entity_id')
            ->addAttributeToFilter('product_tag', [['eq' => '1,2'], ['eq' => '1']])
            ->addFieldToFilter('is_plp', ['eq' => 1])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);
        foreach ($newProductCollectionData as $newProduct) {
            $npiProductIds[] = $newProduct->getEntityId();
        }
        return $npiProductIds;
    }

    /**
     * Get bestsellers for sigungu code
     *
     * @param string $sigunguCode
     * @param int $count
     * @param array $excludeIds
     * @return array
     */
    private function getBestsellerProducts($sigunguCode, $count = self::TOTAL_BESTSELLER_EXCLUDING_NPI, $excludeIds = [])
    {
        $bestSellers = $this->bestsellersFactory->create()->getCollection();
        $bestSellers->addFieldToFilter('sigungu_code', ['eq' => $sigunguCode]);
        if (!empty($excludeIds)) {
            $bestSellers->addFieldToFilter('product_id', ['nin' => $excludeIds]);
        }
        $bestSellers->getSelect()->columns(['total_qty' => new \Zend_Db_Expr('SUM(qty)')])->group('product_id');
        $bestSellers->setOrder('total_qty', 'DSC');

        $bestSellerId = [];
        foreach($bestSellers as $bestSellerdata) {
            $bestSellerId[$bestSellerdata['product_id']] = $bestSellerdata['product_id'];
        } 

        $enableProductIds = $this->getEnableProducts($bestSellerId);

        $productIds = [];
        $i = 1;
        foreach ($enableProductIds as $bestSeller) {
            $productIds[] = $bestSeller;
            if ($i >= $count) {
                break;
            }
            $i++;
        }
        return $productIds;
    }

    /**
     * Get bestsellers for sigungu code
     *
     * @param string $sigunguCode
     * @param int $count
     * @return array
     */
    private function getBestsellerProductsRl($sigunguCode, $count)
    {
        $bestSellerRecordMonth = $this->scopeConfig->getValue("best_sellers/general/plp_best_seller_record_range_in_month");
        if ($bestSellerRecordMonth < 1 || $bestSellerRecordMonth == '') {
            $bestSellerRecordMonth = 3;
        }
        $to = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $from = $this->timezoneInterface->date(strtotime("-".$bestSellerRecordMonth." Months"))->format('Y-m-d');
                
        $bestSellers = $this->bestsellersFactory->create()->getCollection();
        $bestSellers->addFieldToFilter('sigungu_code', ['eq' => $sigunguCode]);
        $bestSellers->addFieldToFilter('created_at', ['gteq' => $from]);
        $bestSellers->addFieldToFilter('created_at', ['lteq' => $to]);
        $bestSellers->getSelect()->columns(['total_qty' => new \Zend_Db_Expr('SUM(qty)')])->group('product_id');
        $bestSellers->setOrder('total_qty', 'DSC');

        $bestSellerId = $enableProductIds = [];
        foreach($bestSellers as $bestSellerdata) {
            $bestSellerId[$bestSellerdata['product_id']] = $bestSellerdata['product_id'];
        }

        $enableProductIds = $this->getEnableProducts($bestSellerId);

        $productIds = [];
        $i = 1;
        foreach ($enableProductIds as $bestSeller) {
            $productIds[] = $bestSeller;
            if ($i >= $count) {
                break;
            }
            $i++;
        }
        return $productIds;
    }

    /**
     * Get bestsellers product
     *
     * @param array $excludeIds
     * @param int $count
     * @return array
     */
    private function getCommonBestsellerProducts($excludeIds, $count = self::TOTAL_BESTSELLER_EXCLUDING_NPI)
    {
        $bestSellers = $this->bestsellersFactory->create()->getCollection();
        if ($excludeIds) {
            $bestSellers->addFieldToFilter('product_id', ['nin' => $excludeIds]);
        }
        $bestSellers->getSelect()->columns(['total_qty' => new \Zend_Db_Expr('SUM(qty)')])->group('product_id');
        $bestSellers->setOrder('total_qty', 'DSC');

        $bestSellerId = $enableProductIds = [];
        foreach($bestSellers as $bestSellerdata) {
            $bestSellerId[$bestSellerdata['product_id']] = $bestSellerdata['product_id'];
        }

        $enableProductIds = $this->getEnableProducts($bestSellerId);

        $productIds = [];
        $npiProducts = $this->getNpiProducts();
        $i = 1;
        foreach ($enableProductIds as $bestSeller) {
            $productIds[] = $bestSeller;
            if ($i >= $count) {
                break;
            }
            if (!in_array($bestSeller, $npiProducts)) {
                $i++;
            }
        }
        return $productIds;
    }

    /**
     * Get bestsellers product
     *
     * @param array $excludeIds
     * @param int $count
     * @return array
     */
    private function getCommonBestsellerProductsRl($excludeIds, $count)
    {
        $bestSellers = $this->bestsellersFactory->create()->getCollection();
        if ($excludeIds) {
            $bestSellers->addFieldToFilter('product_id', ['nin' => $excludeIds]);
        }
        $bestSellers->getSelect()->columns(['total_qty' => new \Zend_Db_Expr('SUM(qty)')])->group('product_id');
        $bestSellers->setOrder('total_qty', 'DSC');

        $bestSellerId = $enableProductIds = [];
        foreach($bestSellers as $bestSellerdata) {
            $bestSellerId[$bestSellerdata['product_id']] = $bestSellerdata['product_id'];
        }

        $enableProductIds = $this->getEnableProducts($bestSellerId);

        $productIds = [];
        $i = 1;
        foreach ($enableProductIds as $bestSeller) {
            $productIds[] = $bestSeller;
            if ($i >= $count) {
                break;
            }
            $i++;
        }
        return $productIds;
    }

    /**
     * Create/Update record for bestsellers product table
     *
     * @param string $sigunguCode
     * @param array $productIds
     * @return void
     */
    private function createUpdateRecord($sigunguCode, $productIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bat_bestseller_products');
        $bestsellerRecord = $this->getProductIds($sigunguCode);
        if (!empty($bestsellerRecord)) {//update record
            $id = $bestsellerRecord['id'];
            $productIdsNew = $bestsellerRecord['product_ids'];
            if ($productIds != $productIdsNew) {
                $data = ["product_ids" => $productIds];
                $where = ['id = ?' => (int)$id];
                $connection->update($tableName, $data, $where);
            }
        } else {//create new record
            $data = ['sigungu_code' => $sigunguCode, 'product_ids' => $productIds];
            $connection->insert($tableName, $data);
        }
    }

    /**
     * Create/Update record for bestsellers product table
     *
     * @param string $sigunguCode
     * @param array $productIds
     * @return void
     */
    private function createUpdateRlRecord($sigunguCode, $productIds)
    {
        $connectionRl = $this->resourceConnection->getConnection();
        $tableNameRl = $this->resourceConnection->getTableName('bat_bestseller_products_rl');
        $bestsellerRecord = $this->getProductIdsRl($sigunguCode);
        
        if (!empty($bestsellerRecord)) {//update record
            $id = $bestsellerRecord['id'];
            $productIdsNew = $bestsellerRecord['product_ids'];
            if ($productIds != $productIdsNew) {
                $data = ["product_ids" => $productIds];
                $where = ['id = ?' => (int)$id];
                $connectionRl->update($tableNameRl, $data, $where);
            }
        } else {//create new record
            $data = ['sigungu_code' => $sigunguCode, 'product_ids' => $productIds];
            $connectionRl->insert($tableNameRl, $data);
        }
    }

    /**
     * Get bestsellers products by sigungu code
     *
     * @param string $sigunguCode
     * @return mixed
     */
    private function getProductIds($sigunguCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bat_bestseller_products');
        $select = $connection->select()
            ->from(
                ['c' => $tableName],
                ['id', 'product_ids']
            )->where(
                'c.sigungu_code = ?',
                $sigunguCode
            );
        return $connection->fetchRow($select);
    }

    /**
     * Get bestsellers products by sigungu code
     *
     * @param string $sigunguCode
     * @return mixed
     */
    private function getProductIdsRl($sigunguCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bat_bestseller_products_rl');
        $select = $connection->select()
            ->from(
                ['c' => $tableName],
                ['id', 'product_ids']
            )->where(
                'c.sigungu_code = ?',
                $sigunguCode
            );
        return $connection->fetchRow($select);
    } 

    public function getEnableProducts($productIds) {
        $enabledProductIds = [];
        $enabledProductCollection = $this->productCollectionFactory->create();
        $enabledProductCollection->addAttributeToSelect('entity_id')
                            ->addIdFilter($productIds)
                            ->addFieldToFilter('is_plp', ['eq' => 1])
                            ->addMinimalPrice()
                            ->addFinalPrice()
                            ->addTaxPercents();
        $enabledProductCollection->addAttributeToFilter('status', 1);
        $enabledProductCollection->addAttributeToFilter('visibility',['neq' => 1]);
        foreach($enabledProductCollection as $dataCollection) {
            $enabledProductIds[$dataCollection->getId()] = $dataCollection->getId();
        }
        $enableSortedArray = $enabledProductIdss = [];
        $enabledProductIdss = array_intersect_key($productIds,$enabledProductIds);
        return $enabledProductIdss;
    } 
}
