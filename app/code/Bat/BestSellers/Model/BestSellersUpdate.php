<?php

namespace Bat\BestSellers\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\BestSellers\Model\BestSellersFactory;

class BestSellersUpdate extends AbstractModel
{
    /**
     * @var BestSellersFactory
     */
    private $bestSellersFactory;
    
    /**
     * PriceMaster Update Construct
     *
     * @param BestSellersFactory $bestSellersFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        BestSellersFactory $bestSellersFactory
    ) {
        $this->bestSellersFactory = $bestSellersFactory;
    }

    /**
     * Add/Update Qty as per sigungu code and sku
     *
     * @param string $sku
     * @param string $productId
     * @param string $sigunguCode
     * @param string $qty
     * @return array
     */
    public function addUpdateQtyBestSeller($sku, $productId, $sigunguCode, $qty)
    {
        /*$bestSellerCollection = $this->bestSellersFactory->create()->getCollection()
                        ->addFieldToFilter('sigungu_code', $sigunguCode)
                        ->addFieldToFilter('sku', $sku)
                        ->setOrder('id', 'DESC')
                        ->load();
        if(count($bestSellerCollection->getData()) > 0) {
            $data = $bestSellerCollection->getData();
            $entityId = $data[0]['id'];
            $existingQty = $data[0]['qty'];
            $bestSeller = $this->bestSellersFactory->create()->load($entityId);
            $bestSeller->setQty($existingQty + $qty);
            $bestSeller->save();
        }else {*/
            $bestSeller = $this->bestSellersFactory->create();
            $bestSeller->setSku($sku);
            $bestSeller->setSigunguCode($sigunguCode);
            $bestSeller->setQty($qty);
            $bestSeller->setProductId($productId);
            $bestSeller->save();
        //}
    }
}
