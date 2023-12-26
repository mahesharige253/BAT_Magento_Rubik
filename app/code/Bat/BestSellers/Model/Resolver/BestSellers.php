<?php

namespace Bat\BestSellers\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Catalog\Helper\Image as ImageHelper;
use Bat\BestSellers\Model\ResourceModel\BestSellers\CollectionFactory as BestSellersCollectionFactory;
use Bat\BestSellers\Model\GetBestSellers;

/**
 * Bset seller resolver, used for GraphQL request processing.
 */
class BestSellers implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var BestSellersCollectionFactory
     */
    protected $bestSellersCollectionFactory;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var GetCustomer
     */
    protected $getCustomer;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @var GetBestSellers
     */
    protected $getbestSeller;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param BestSellersCollectionFactory $bestSellersCollectionFactory
     * @param ImageHelper $imageHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductFactory $productFactory
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param GetCustomer $getCustomer
     * @param TimezoneInterface $timezoneInterface
     * @param GetBestSellers $getbestSeller
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        BestSellersCollectionFactory $bestSellersCollectionFactory,
        ImageHelper $imageHelper,
        ScopeConfigInterface $scopeConfig,
        ProductFactory $productFactory,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        GetCustomer $getCustomer,
        TimezoneInterface $timezoneInterface,
        GetBestSellers $getbestSeller
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->bestSellersCollectionFactory = $bestSellersCollectionFactory;
        $this->imageHelper = $imageHelper;
        $this->scopeConfig = $scopeConfig;
        $this->productFactory = $productFactory;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->getCustomer = $getCustomer;
        $this->timezoneInterface = $timezoneInterface;
        $this->getbestSeller = $getbestSeller;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $productIds = [];
        $areaCode = $args['areaCode'];

        $enabledStatus = $this->scopeConfig->getValue("best_sellers/general/best_seller_carousel");
        $frequentlyOrderedProductId = '';

        if ((false === $context->getExtensionAttributes()->getIsCustomer())) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        } else {
            $customer = $this->getCustomer->execute($context);
            $customAttributes = $customer->getCustomAttributes();
            if (isset($customAttributes['bat_frequently_ordered'])) {
                $frequentlyOrderedProductId = $customAttributes['bat_frequently_ordered']->getValue();
            }
        }
        
        $data = [];
        $productData = [];
        $areaProductIds = [];
        if ($enabledStatus || isset($args['requestFrom'])) {
            if (($areaCode == '') || (!is_numeric($areaCode))) {
                throw new GraphQlInputException(__('Area code is not valid'));
            }
            
            if (isset($args['requestFrom']) && $args['requestFrom'] == 'rl') {
                $productBestSellerCollection = $this->getbestSeller->getBestSellersRl($areaCode);
            } else {
                $homepageCarouselLimit = $this->scopeConfig->getValue(
                    "best_sellers/general/best_seller_carousel_limit"
                );
                $productBestSellerCollection = $this->getbestSeller->getBestSellers($areaCode, $homepageCarouselLimit);
            }
            
            $productIdSort = [];
            foreach ($productBestSellerCollection as $productIdData) {
                $productIdSort[$productIdData] = $productIdData;
            } 
            $productArray = [];
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('*')
                            ->addIdFilter($productBestSellerCollection)
                            ->addMinimalPrice()
                            ->addFinalPrice()
                            ->addTaxPercents();

            foreach ($productCollection->getItems() as $product) {
                
                $productId = $product->getId(); 
                $productArray[$productId] = $product->getData();
                if ($productId == $frequentlyOrderedProductId) {
                    $productArray[$productId]['frequent'] = $frequentlyOrderedProductId;
                }
                $productArray[$productId]['best_seller'] = $productId;
                $productArray[$productId]['model'] = $product;
            }
            $finalProductData = [];
            if(count($productIdSort) > 0 && count($productArray) > 0) {
                $finalProductData = $this->getSortedArray($productIdSort, $productArray);
            }
            $data['items'] = $finalProductData;

        } else {
            throw new GraphQlNoSuchEntityException(__('Best Sellers Carousel is Disabled in admin'));
        }

        return $data;
    }

    /**
     * Function to sort array
     *
     * @param array $x
     * @param array $y
     *
     * return array
     */
    public function getSortedArray($x, $y)
    {
        $newarray = [];
        $keys    = array_keys($x);
        $values  = array_values($y);
        for ($x = 0; $x < count($keys); $x++) {
            $newarray[$keys[$x]] = $y[$keys[$x]];
        }
        return $newarray;
    }
}
