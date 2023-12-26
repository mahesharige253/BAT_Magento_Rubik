<?php
namespace Bat\BestSellers\Helper;

use Bat\BestSellers\Model\ResourceModel\BestSellers\CollectionFactory;
use Bat\CatalogGraphQl\Model\Resolver\DataProvider\ProductItems;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;

/**
 * @class Data
 * Helper class for Best Sellers
 */
class Data extends AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $bestSellersCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ProductItems
     */
    private ProductItems $productItems;

    /**
     * @param CollectionFactory $bestSellersCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     * @param ProductItems $productItems
     */
    public function __construct(
        CollectionFactory $bestSellersCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger,
        ProductItems $productItems
    ) {
        $this->bestSellersCollectionFactory = $bestSellersCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->productItems = $productItems;
    }

    /**
     * Get BestSeller Ids
     *
     * @param mixed $sigunguCode
     * @return array
     */
    public function getBestSellers($sigunguCode)
    {
        $npiProductIds = [];
        $bestSellerProductIds = [];
        $newProductCollectionData = $this->productCollectionFactory->create();
        $newProductCollectionData->addFieldToSelect('entity_id')
            ->addAttributeToFilter('product_tag', [['eq'=>'1,2'],['eq'=>'1']])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['neq' => Visibility::VISIBILITY_NOT_VISIBLE]);
        foreach ($newProductCollectionData as $newProduct) {
            $npiProductIds[] = $newProduct->getEntityId();
        }
        $this->logger->info('NPI product Ids from New Product ', $npiProductIds);
        $bestSellers = $this->bestSellersCollectionFactory->create();
        $bestSellers->addFieldToFilter('sigungu_code', ['eq' => $sigunguCode]);
        $bestSellers->getSelect()->columns(['qty' => new \Zend_Db_Expr('SUM(qty)')])->group('product_id');
        $bestSellers->addFieldToFilter('product_id', ['nin' => $npiProductIds]);
        $bestSellers->setOrder('qty', 'DSC');
        $bestSellerCount = 0;
        if (count($bestSellers) > 0) {
            foreach ($bestSellers as $bestSellerProduct) {
                $bestSellerProductIds[] = $bestSellerProduct->getProductId();
                $bestSellerCount ++;
                if ($bestSellerCount >= 5) {
                    break;
                }
            }
        }
        $this->logger->info('First best seller from New Product', $bestSellerProductIds);
        if (count($bestSellerProductIds) < 5) {
            $remainingLimit = 5 - count($bestSellerProductIds);
            $bestSellersWithoutSigunguCode = $this->bestSellersCollectionFactory->create();
            if (!empty($bestSellerProductIds)) {
                $bestSellersWithoutSigunguCode->addFieldToFilter('product_id', ['nin' => $bestSellerProductIds]);
            }
            $bestSellersWithoutSigunguCode->addFieldToFilter('product_id', ['nin' => $npiProductIds]);
            $bestSellersWithoutSigunguCode->getSelect()
                ->columns(['qty' => new \Zend_Db_Expr('SUM(qty)')])->group('product_id');
            $bestSellersWithoutSigunguCode->setOrder('qty', 'DSC');
            $bestSellerCount = 0;
            if (count($bestSellersWithoutSigunguCode) > 0) {
                foreach ($bestSellersWithoutSigunguCode as $bestSellerProduct) {
                    $bestSellerProductIds[] = $bestSellerProduct->getProductId();
                    $bestSellerCount ++;
                    if ($bestSellerCount >= $remainingLimit) {
                        break;
                    }
                }
            }
        }
        $this->logger->info('Second best seller from New Product', $bestSellerProductIds);
        $bestSellerItemIds = $this->productItems->getBestSellerProductIds($sigunguCode);
        $this->logger->info('Third best seller from New Product', $bestSellerItemIds);
        $bestSellerProductIds = array_merge($bestSellerProductIds, $bestSellerItemIds);
        $this->logger->info('Fourth best seller from New Product', $bestSellerProductIds);
        return $bestSellerProductIds;
    }
}
