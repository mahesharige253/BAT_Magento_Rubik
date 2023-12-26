<?php
namespace Bat\Catalog\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @class CatalogProductSaveAfter
 * Check expiry date while product save/update
 */
class CatalogCategorySaveAfter implements ObserverInterface
{
    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var StoreManagerInterface;
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CategoryFactory $categoryFactory
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }
    /**
     * Check expiry date
     *
     * @param EventObserver $observer
     * @return boolean
     */
    public function execute(EventObserver $observer)
    {
        try {
            $connection = $this->resource->getConnection();
            $tableName = $connection->getTableName("catalog_category_product");
            $storeId = $this->storeManager->getWebsite(1)->getDefaultStore()->getId();
            $rootCategoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();
            $rootCategory = $this->categoryFactory->create()->load($rootCategoryId);
            $rootCategoryProductsPosition = $rootCategory->getProductsPosition();
            $subCategories = $rootCategory->getChildrenCategories();
            foreach ($subCategories as $subCat) {
                $subCategoryId = $subCat->getId();
                $this->logger->info('Sub Category id: '.$subCategoryId);
                $subCategory = $this->categoryFactory->create()->load($subCategoryId);
                $subCategoryProductsPosition = $subCategory->getProductsPosition();
                $newSequence = [];
                $finalSequence = [];

                foreach ($subCategoryProductsPosition as $key => $subCategoryProductPosition) {
                    $newSequence[$key] = $rootCategoryProductsPosition[$key];
                }
                $this->logger->info('Before sort ');
                asort($newSequence);
                $i = 0;
                $this->logger->info('After Ksort ');
                foreach($newSequence as $key => $value) {
                    $finalSequence[] = ["category_id" => $subCategoryId, "product_id" => $key, "position" => $i];
                    $i++;
                }
                $this->logger->info('PLP sequence: ');
                $this->logger->info(json_encode($finalSequence));
                $connection->insertOnDuplicate($tableName, $finalSequence);
            }
        }
        catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
