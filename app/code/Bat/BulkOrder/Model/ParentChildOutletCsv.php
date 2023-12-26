<?php
namespace Bat\BulkOrder\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Bat\Customer\Helper\Data;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Bat\BulkOrder\Block\Adminhtml\ChildOutlet;

class ParentChildOutletCsv
{
    /**
     * Cron customer bulk_order path
     */
    public const PARENTCHILDCUSTOMER_CSV_PATH = "customer/bulk_order/";

    /**
     * Cron log enabled path
     */
    public const LOG_ENABLED_PATH = "bat_bulkorder/bulkorder/log_enabled";

    /**
     * @var CollectionFactory
     */
    protected $productFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Csv
     */
    protected $csvProcessor;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $driverFile;

    /**
     * @var CompanyManagementInterface
     */
    private $companyRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductCollection
     */
     protected $productCollection = null;

     /**
      * @var ChildOutlet
      */ 
     protected $childOutlet;

    /**
     * @param CollectionFactory $productFactory
     * @param Filesystem $filesystem
     * @param Data $helper
     * @param Csv $csvProcessor
     * @param DirectoryList $directoryList
     * @param File $driverFile
     * @param CompanyManagementInterface $companyRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ChildOutlet $childOutlet
     */
    public function __construct(
        CollectionFactory $productFactory,
        Filesystem $filesystem,
        Data $helper,
        Csv $csvProcessor,
        DirectoryList $directoryList,
        File $driverFile,
        CompanyManagementInterface $companyRepository,
        ScopeConfigInterface $scopeConfig,
        ChildOutlet $childOutlet
    ) {
        $this->productFactory = $productFactory;
        $this->filesystem = $filesystem;
        $this->helper = $helper;
        $this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->companyRepository = $companyRepository;
        $this->scopeConfig = $scopeConfig;
        $this->childOutlet = $childOutlet;
    }

    /**
     * Get products
     *
     * @return array
     */
    public function getPriceTagProducts()
    {
        if ($this->productCollection == null) {
            $collection = $this->productFactory->create();
            $collection->addAttributeToSelect('pricetag_type');
            $collection->addAttributeToSelect('name');
            $collection->addAttributeToSelect('sku');
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collection->addAttributeToFilter('pricetag_type', ['in' => [1]]);
            $collection->addAttributeToFilter('visibility', ['eq' => Visibility::VISIBILITY_NOT_VISIBLE]);
            $collection->joinField(
                'stock_status',
                'cataloginventory_stock_status',
                'stock_status',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )->addFieldToFilter('stock_status', ['eq' => StockStatus::STATUS_IN_STOCK]);
            $this->productCollection = $collection;
        }
        return $this->productCollection;
    }

    /**
     * Generate Outlet Csv for all the products
     *
     * @return
     */
    public function generateOutletCsv()
    {
        try {
            $this->checkFolderDir();
            $parentCustomers = $this->helper->getCustomer('is_parent', 1);
            foreach ($parentCustomers as $parentCustomer) {
                $outletId = $parentCustomer->getOutletId();
                $this->setChildOutletWithProductData($outletId, $parentCustomer->getId());
            }
        } catch (Exception $e) {
            $this->logCustomerExportRequest($e->getMessage());
        }
    }

    /**
     * Get Child customer
     *
     * @param int $parentOutletId
     * @return object
     */
    public function getChildOutlet($parentOutletId)
    {
        $customer =  $this->helper->getCustomer('parent_outlet_id', $parentOutletId);
        return $customer;
    }

    /**
     * Get parent outlet
     *
     * @param int $outletId
     * @return array
     */
    public function isParentOutlet($outletId)
    {
        $customer = $this->helper->getParentOutlet($outletId);
        $parent = $customer->getFirstItem()->getData();
        if ($parent['is_parent']) {
            try {
                $this->setChildOutletWithProductData($outletId, $parent['entity_id']);
                return true;
            } catch (Exception $e) {
                $this->logCustomerExportRequest($e->getMessage());
            }
        }
        return false;
    }

    /**
     * Set Child Outlet With Product Data
     *
     * @param int $outletId
     * @param int $parentId
     * @return
     */
    public function setChildOutletWithProductData($outletId, $parentId)
    {   
        try{
        $products = $this->childOutlet->getCategorySequenceProducts();
        $priceTagProducts = $this->getPriceTagProducts();
        $childCustomers = $this->getChildOutlet($outletId);
        if (($outletId !='') && count($childCustomers) > 0) {
            $content = [];
            $headerData = [];
                $company = $this->companyRepository->getByCustomerId($parentId);
                $companyName = $company->getCompanyName();
                $headerData[] = ['SKU', 'Product Name', $outletId.':'.$companyName];
                $headerData[0][2] = $outletId.":".$companyName;
                $i = 3;
                $qtyrow = [];
            foreach ($childCustomers as $key => $childCustomer) {
                if ($childCustomer->getOutletId() != '') {
                    $company = $this->companyRepository->getByCustomerId($childCustomer->getId());
                    if (!empty($company)) {
                        $companyName = $company->getCompanyName();
                        $childOutletId = $childCustomer->getOutletId();
                        $headerData[0][$i] = $childOutletId.":".$companyName;
                        $qtyrow[] = 0;
                        $i++;
                    }
                }
            }
            foreach ($products as $product) {
                $producData = [$product->getSku(), $product->getName(), 0];
                $content[] = $this->arrayMergeData($producData, $qtyrow);
            }

            foreach ($priceTagProducts as $item) {
                $itemName = mb_convert_encoding($item->getName(), "UTF-8");
                $itemSku = mb_convert_encoding($item->getSku(), "UTF-8");
                $priceTagProductData = [$itemSku, $itemName, 0];
                $content[] = $this->arrayMergeData($priceTagProductData, $qtyrow);
            }

            $dataMerge = $this->arrayMergeData($headerData, $content);
            $fileName = 'BulkOrder_'.$outletId.'.csv'; // Add Your CSV File name
            $filePath =  $this->directoryList->getPath(DirectoryList::MEDIA) . "/"
            .self::PARENTCHILDCUSTOMER_CSV_PATH .$fileName;
            $this->csvProcessor->setEnclosure('"')->setDelimiter(',')->saveData($filePath, $dataMerge);
        }
    } catch(NoSuchEntityException $e){
        $this->addLog('Bulk order csv not generated for parent: '.$parentId. ' child: '.$outletId);
        $this->addLog('Error: '.$e->getMessage());
    } catch(\Exception $e){ 
        $this->addLog('Bulk order csv not generated for parent: '.$parentId. ' child: '.$outletId);
        $this->addLog('Error: '.$e->getMessage());
    }

    }

    /**
     * Get filesysytem
     *
     * @return object
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Add Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addLog($message)
    {
        $config = $this->getConfig(self::LOG_ENABLED_PATH);
        if ($config) {
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/bulk_order_cron.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($message);
        }
    }

    /**
     * Array merge
     *
     * @param array $arrayDataFirst
     * @param array $arrayDataSecond
     */
    public function arrayMergeData($arrayDataFirst, $arrayDataSecond)
    {
        return array_merge($arrayDataFirst, $arrayDataSecond);
    }

    /**
     * Check Folder Directory
     *
     * @return
     */
    public function checkFolderDir()
    {
        $mediaDirectory = $this->getFilesystem()->getDirectoryWrite((DirectoryList::MEDIA));
        $mediaCsvRootDir = $this->getFilesystem()->getDirectoryRead(DirectoryList::MEDIA)
               ->getAbsolutePath(self::PARENTCHILDCUSTOMER_CSV_PATH);
        if (!$this->driverFile->isExists($mediaCsvRootDir)) {
            $mediaDirectory->create(self::PARENTCHILDCUSTOMER_CSV_PATH);
        }
    }

    /**
     * Get Config
     *
     * @param string $config_path
     * @return boolean
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
