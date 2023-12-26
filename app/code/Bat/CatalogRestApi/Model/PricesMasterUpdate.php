<?php

namespace Bat\CatalogRestApi\Model;

use Bat\CatalogRestApi\Model\PriceMasterFactory;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class PricesMasterUpdate
{

    /**
     * Price master Group id R
     */
    public const CUSTOMER_GROUP_R = "R";

    /**
     * Price master Group id S
     */
    public const CUSTOMER_GROUP_S = "S";

    /**
     * Price master status path
     */
    public const ENABLE_STATUS = "bat_price_master/pricemaster/status";

    /**
     * Cron log enabled path
     */
    public const LOG_ENABLED_PATH = "bat_price_master/pricemaster/log_enabled";

    /**
     * @var PriceMasterFactory
     */
    protected $priceMasterFactory;

    /**
     * @var ProductAction
     */
    private $productAction;
    
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * PriceMaster Update Construct
     *
     * @param PriceMasterFactory $priceMasterFactory
     * @param ProductAction $action
     * @param CollectionFactory $productCollectionFactory
     * @param TimezoneInterface $timezoneInterface
     * @param ScopeConfigInterface $scopeConfig
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */

    public function __construct(
        PriceMasterFactory $priceMasterFactory,
        ProductAction $action,
        CollectionFactory $productCollectionFactory,
        TimezoneInterface $timezoneInterface,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->priceMasterFactory = $priceMasterFactory;
        $this->productAction = $action;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Update Product Price
     */
    public function updateProductPrice()
    {
        try {
            $productCollection = $this->productCollectionFactory->create();
            foreach ($productCollection as $product) {
                $priceMasterRList = $this->checkSameDateForMultipleItem($product->getSku(), self::CUSTOMER_GROUP_R);
                $i = 0;
                if (!empty($priceMasterRList)) {
                    $this->unUsedItemUpdateStatus($product->getSku(), self::CUSTOMER_GROUP_R);
                    foreach ($priceMasterRList as $priceMasterRitem) {
                        if ($i == 0) {
                            $this->setPriceAttribute(
                                $product->getId(),
                                $priceMasterRitem['price'],
                                self::CUSTOMER_GROUP_R,
                                $priceMasterRitem['content']
                            );
                            $this->updatePriceMasterItem($priceMasterRitem['id'], 1);
                        } else {
                            $this->updatePriceMasterItem($priceMasterRitem['id'], 2);
                        }
                        $i++;
                    }

                }
                $priceMasterSList = $this->checkSameDateForMultipleItem($product->getSku(), self::CUSTOMER_GROUP_S);
                $j = 0;
                if (!empty($priceMasterSList)) {
                    $this->unUsedItemUpdateStatus($product->getSku(), self::CUSTOMER_GROUP_S);
                    foreach ($priceMasterSList as $priceMasterSitem) {
                        if ($j == 0) {
                            $this->setPriceAttribute(
                                $product->getId(),
                                $priceMasterSitem['price'],
                                self::CUSTOMER_GROUP_S,
                                $priceMasterSitem['content']
                            );
                            $this->updatePriceMasterItem($priceMasterSitem['id'], 1);
                        } else {
                            $this->updatePriceMasterItem($priceMasterSitem['id'], 2);
                        }
                        $j++;
                    }

                }
            }
        } catch (Exception $e) {
            $this->addLog($e->getMessage());
        }
    }

    /**
     * Set price attributes data
     *
     * @param int $productId
     * @param mix $price
     * @param string $customerGroupId
     * @param string $content
     */
    public function setPriceAttribute($productId, $price, $customerGroupId, $content)
    {
        $attributesArray = [];
        if ($customerGroupId == self::CUSTOMER_GROUP_R) {
            $attributesArray['price'] = $price;
        } elseif ($customerGroupId == self::CUSTOMER_GROUP_S) {
            $attributesArray['consumer_price'] = $price;
        }
        $content = json_decode($content, true);
        foreach ($content['extension_attributes'] as $key => $attributes) {
            foreach ($attributes as $key => $value) {
                if ($key == 'effective_date') {
                    if ($customerGroupId == self::CUSTOMER_GROUP_S) {
                        $attributesArray['consumer_price_effective_date'] = $value;
                    } else {
                        $attributesArray['effective_date'] = $value;
                    }
                } elseif ($key != 'customer_group_id') {
                    $attributesArray[$key] = $value;
                }
            }
        }
        $this->productAction->updateAttributes([$productId], $attributesArray, 0);
    }

    /**
     * Check Same Date For Multiple Item
     *
     * @param string $sku
     * @param string $customerGroupId
     * @return array
     */
    public function checkSameDateForMultipleItem($sku, $customerGroupId)
    {
        $finalDate = $this->getFinalDate($sku, $customerGroupId);
        $priceMaster = $this->priceMasterFactory->create()->getCollection()
                        ->addFieldToFilter('customer_group_id', $customerGroupId)
                        ->addFieldToFilter('sku', $sku)
                        ->addFieldToFilter('effective_date', $finalDate)
                        ->setOrder('created_at', 'DESC')
                        ->addFieldToFilter('is_picked', ['in' => [0,1]])
                        ->load();
        return $priceMaster;
    }

    /**
     * Un Used Item Update Status
     *
     * @param string $sku
     * @param string $customerGroupId
     * @return
     */
    public function unUsedItemUpdateStatus($sku, $customerGroupId)
    {
        $currentDate = $this->timezoneInterface->date()->format('m/d/Y h:i A');
        $finalDate = $this->getFinalDate($sku, $customerGroupId);
        $priceMasterNotMatch = $this->priceMasterFactory->create()->getCollection()
                        ->addFieldToFilter('customer_group_id', $customerGroupId)
                        ->addFieldToFilter('sku', $sku)
                        ->addFieldToFilter('effective_date', ['neq' => $finalDate])
                        ->load();
        if ($priceMasterNotMatch->getSize() > 0) {
            foreach ($priceMasterNotMatch as $item) {
                if (strtotime($currentDate) >= strtotime($item->getEffectiveDate())) {
                    $this->updatePriceMasterItem($item['id'], 2);
                }
            }
        }
    }

    /**
     * Update price master item status
     *
     * @param int $id
     * @param int $status
     * @return
     */
    public function updatePriceMasterItem($id, $status)
    {
        $priceMaster = $this->priceMasterFactory->create()->load($id);
        $priceMaster->setIsPicked($status);
        $priceMaster->save();
    }

    /**
     * Get Final Date
     *
     * @param string $sku
     * @param string $customerGroupId
     * @return array
     */
    public function getFinalDate($sku, $customerGroupId)
    {
        $currentDate = $this->timezoneInterface->date()->format('m/d/Y h:i A');
        $priceDateList = $this->getDateList($sku, $customerGroupId);
        return $this->getNearestDate($priceDateList, $currentDate);
    }

    /**
     * Get Date List
     *
     * @param string $sku
     * @param string $customerGroupId
     * @return array
     */
    public function getDateList($sku, $customerGroupId)
    {
        $currentDate = $this->timezoneInterface->date()->format('m/d/Y h:i A');
        $priceMaster = $this->priceMasterFactory->create()->getCollection()
            ->addFieldToFilter('customer_group_id', $customerGroupId)
            ->addFieldToFilter('sku', $sku)
            ->addFieldToFilter('is_picked', ['in' => [0,1]])
            ->load();
        $alldates = [];
        foreach ($priceMaster as $priceItem) {
            if (strtotime($currentDate) >= strtotime($priceItem->getEffectiveDate())) {
                $alldates[] = $priceItem->getEffectiveDate();
            }
        }
        return $alldates;
    }

    /**
     * Get Nearest Date
     *
     * @param string $dates
     * @param string $targetDate
     * @return string
     */
    public function getNearestDate($dates, $targetDate)
    {
        $closestDate = null;
        $minDiff = PHP_INT_MAX;
        // Loop through the array of dates
        foreach ($dates as $date) {
            // Convert dates to Unix timestamps for easier comparison
            $dateTimestamp = strtotime($date);
            $targetTimestamp = strtotime($targetDate);
            // Calculate the absolute time difference
            $diff = abs($dateTimestamp - $targetTimestamp);
            // Check if this date is closer than the previously found closest date
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closestDate = $date;
            }
        }
        return $closestDate;
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
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/price_master_cron.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($message);
        }
    }

    /**
     * Get Status
     */
    public function getStatus()
    {
        return $this->getConfig(self::ENABLE_STATUS);
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
