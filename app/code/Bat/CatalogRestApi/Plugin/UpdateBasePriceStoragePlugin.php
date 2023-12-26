<?php
namespace Bat\CatalogRestApi\Plugin;

use Magento\Catalog\Model\Product\Price\BasePriceStorage;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Bat\CatalogRestApi\Model\PriceMasterFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Bat\CatalogRestApi\Model\PricesMasterUpdate;

class UpdateBasePriceStoragePlugin
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollection;

    /**
     * @var ProductAction
     */
    private ProductAction $productAction;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var PriceMasterFactory
     */
    private PriceMasterFactory $priceMasterFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * UpdateBasePriceStoragePlugin constructor
     *
     * @param CollectionFactory $collection
     * @param ProductAction $action
     * @param TimezoneInterface $timezoneInterface
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param PriceMasterFactory $priceMasterFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CollectionFactory $collection,
        ProductAction $action,
        TimezoneInterface $timezoneInterface,
        ProductRepositoryInterface $productRepositoryInterface,
        PriceMasterFactory $priceMasterFactory,
        SerializerInterface $serializer
    ) {
        $this->productCollection = $collection;
        $this->productAction = $action;
        $this->timezoneInterface = $timezoneInterface;
        $this->productRepository = $productRepositoryInterface;
        $this->priceMasterFactory = $priceMasterFactory;
        $this->serializer = $serializer;
    }

    /**
     * Update custom attribute values
     *
     * @param BasePriceStorage $subject
     * @param object $result
     * @param array|BasePriceInterface[] $prices
     */
    public function afterUpdate(BasePriceStorage $subject, $result, array $prices)
    {

        $currentDate = $this->timezoneInterface->date()->format('m/d/Y h:i A');
        
        foreach ($prices as $price) {
            $priceMasterFactory = $this->priceMasterFactory->create();
            $priceMasterFactory->setSku($price->getSku());
            $product = $this->productRepository->get($price->getSku());
            $attributesArray = [];
            $customerGroupId = '';
            
            if ($price->getExtensionAttributes()->getBatchId()) {
                $attributesArray['batch_id'] = $price->getExtensionAttributes()->getBatchId();
            }
            if ($price->getExtensionAttributes()->getCompanyCode()) {
                $attributesArray['company_code'] = $price->getExtensionAttributes()->getCompanyCode();
            }
            if ($price->getExtensionAttributes()->getCreatedAt()) {
                 $attributesArray['price_created_at'] = $price->getExtensionAttributes()->getCreatedAt();
            }
            if ($price->getExtensionAttributes()->getCountryCode()) {
                $attributesArray['country_code'] = $price->getExtensionAttributes()->getCountryCode();
            }
            if ($price->getExtensionAttributes()->getIdocReferenceNumber()) {
                $attributesArray['idoc_reference_number'] =
                    $price->getExtensionAttributes()->getIdocReferenceNumber();
            }
            if ($price->getExtensionAttributes()->getUom()) {
                $attributesArray['uom'] = $price->getExtensionAttributes()->getUom();
            }
            if ($price->getExtensionAttributes()->getPriceGroupNumber()) {
                $attributesArray['price_group_number'] = $price->getExtensionAttributes()->getPriceGroupNumber();
            }
            if ($price->getExtensionAttributes()->getCustomerGroupCode()) {
                $attributesArray['customer_group_code'] = $price->getExtensionAttributes()->getCustomerGroupCode();
            }
            if ($price->getExtensionAttributes()->getCustomerGroupId()) {
                $customerGroupId = $price->getExtensionAttributes()->getCustomerGroupId();
                $priceMasterFactory->setCustomerGroupId($customerGroupId);
            }
            if ($price->getExtensionAttributes()->getCurrencyCode()) {
                $attributesArray['currency_code'] = $price->getExtensionAttributes()->getCurrencyCode();
            }
            $effectiveDate = $price->getExtensionAttributes()->getEffectiveDate();
            if ($effectiveDate) {
                $priceMasterFactory->setEffectiveDate($effectiveDate);
            }
            if ($price->getExtensionAttributes()->getConditionSequence()) {
                $attributesArray['condition_sequence'] = $price->getExtensionAttributes()->getConditionSequence();
            }
            if ($price->getExtensionAttributes()->getCustomerGroup()) {
                $attributesArray['customer_group'] = $price->getExtensionAttributes()->getCustomerGroup();
            }
            if ($price->getExtensionAttributes()->getConditionName()) {
                $attributesArray['condition_name'] = $price->getExtensionAttributes()->getConditionName();
            }
            if ($price->getExtensionAttributes()->getConditionValue()) {
                $attributesArray['condition_value'] = $price->getExtensionAttributes()->getConditionValue();
            }
            if ($price->getExtensionAttributes()->getTotalPriceItem()) {
                $attributesArray['total_price_item'] = $price->getExtensionAttributes()->getTotalPriceItem();
            }
            if ($price->getExtensionAttributes()->getBaseToSecondaryUom()) {
                $baseToSecondaryUom = $price->getExtensionAttributes()->getBaseToSecondaryUom();
            } else {
                $baseToSecondaryUom = $product->getBaseToSecondaryUom();
            }
            $finalPrice = $this->getPriceCalculation($price->getCustomPriceData(), $baseToSecondaryUom);
            $priceMasterFactory->setPrice($finalPrice);

            if (($product->getEffectiveDate() != '')
                && strtotime($product->getEffectiveDate()) > strtotime($effectiveDate)) {
                $originalPrice['price'] = $price->getPrice();
                $this->productAction->updateAttributes([$product->getId()], $originalPrice, 0);
                $price->setPrice($price->getCustomPriceData());
                unset($price['custom_price_data']);
                $pricesData = $price;
                $extAttr = (array)$pricesData->getExtensionAttributes();
                $pricesData['extension_attributes'] = $extAttr;
                $allPrices[] = $pricesData;

                $priceMasterFactory->setContent($this->serializer->serialize($pricesData->getData()));
                $priceMasterFactory->setIsPicked(2);
                $priceMasterFactory->save();
               
            } elseif (strtotime($currentDate) < strtotime($effectiveDate)) {
                $originalPrice['price'] = $price->getPrice();
                $this->productAction->updateAttributes([$product->getId()], $originalPrice, 0);
                $price->setPrice($price->getCustomPriceData());
                unset($price['custom_price_data']);
                $pricesData = $price;
                $extAttr = (array)$pricesData->getExtensionAttributes();
                $pricesData['extension_attributes'] = $extAttr;
                $allPrices[] = $pricesData;
                $priceMasterFactory->setContent($this->serializer->serialize($pricesData->getData()));
                $priceMasterFactory->save();
                
            } else {
                if ($customerGroupId == PricesMasterUpdate::CUSTOMER_GROUP_R) {
                    $attributesArray['price'] = $finalPrice;
                    $attributesArray['effective_date'] = $effectiveDate;
                } elseif ($customerGroupId == PricesMasterUpdate::CUSTOMER_GROUP_S) {
                    $attributesArray['consumer_price'] = $finalPrice;
                    $attributesArray['consumer_price_effective_date'] = $effectiveDate;
                }
            
                $this->productAction->updateAttributes([$product->getId()], $attributesArray, 0);
                $priceMasterFactory->setIsPicked(1);
                unset($price['custom_price_data']);
                $pricesData = $price;
                $extAttr = (array)$pricesData->getExtensionAttributes();
                $pricesData['extension_attributes'] = $extAttr;
                $allPrices[] = $pricesData;

                $priceMasterFactory->setContent($this->serializer->serialize($pricesData->getData()));
                $priceMasterFactory->save();
            }
        }
       
        return $result;
    }

    /**
     * Get Price Calculation
     *
     * @param mixed $price
     * @param string $baseToSecondaryUom
     * @return mixed
     */
    public function getPriceCalculation($price, $baseToSecondaryUom)
    {
        $finalPrice = ($price/1000)* $baseToSecondaryUom;
        return $finalPrice;
    }

    /**
     * Set price attribute values
     *
     * @param BasePriceStorage $subject
     * @param array|BasePriceInterface[] $prices
     */
    public function beforeUpdate(BasePriceStorage $subject, array $prices)
    {
        $currentDate = $this->timezoneInterface->date()->format('m/d/Y h:i A');
        foreach ($prices as $price) {
            $product = $this->productRepository->get($price->getSku());
            $effectiveDate = $price->getExtensionAttributes()->getEffectiveDate();
            if (($product->getEffectiveDate() !='')
                && strtotime($product->getEffectiveDate()) > strtotime($effectiveDate)) {
                $price->setCustomPriceData($price->getPrice());
                $price->setPrice($product->getPrice());
            } elseif (strtotime($currentDate) < strtotime($effectiveDate)) {
                $price->setCustomPriceData($price->getPrice());
                $price->setPrice($product->getPrice());
            } else {
                $price->setCustomPriceData($price->getPrice());
            }
        }
    }
}
