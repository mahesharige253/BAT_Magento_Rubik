<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\RequisitionList\Model\Resolver\RequisitionList;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin\Collection;
use Bat\RequisitionList\Model\RequisitionListAdminFactory;
use Bat\RequisitionList\Model\NormalSeasonalOtherRlItems;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class GetAdminRequisitionListItems
{

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var RequisitionListAdminFactory
     */
    private $requisitionListAdminFactory;

    /**
     * @var NormalSeasonalOtherRlItems
     */
    protected $adminRlItems;

    /**
     * @var LoggerInterface
     */
     protected $logger;

     /**
      * @param ProductRepositoryInterface $productRepository
      * @param Collection $collection
      * @param RequisitionListAdminFactory $requisitionListAdminFactory
      * @param NormalSeasonalOtherRlItems $adminRlItems
      * @param LoggerInterface $logger
      */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Collection $collection,
        RequisitionListAdminFactory $requisitionListAdminFactory,
        NormalSeasonalOtherRlItems $adminRlItems,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->collection = $collection;
        $this->requisitionListAdminFactory = $requisitionListAdminFactory;
        $this->adminRlItems = $adminRlItems;
        $this->logger = $logger;
    }

    /**
     * Get Admin Rl Product Function
     *
     * @param array $requisitionListIds
     * @param int $customerId
     * @return array
     */
    public function getadminRequisitionListData(array $requisitionListIds, $customerId)
    {
        $adminRl = $this->requisitionListAdminFactory->create()->getCollection();
        $adminRl->addFieldToFilter('status', ['eq'=>1]);
        $adminRl->addFieldToFilter('entity_id', ['eq'=> $requisitionListIds]);
        $rlType = $adminRl->getFirstItem()->getData('rl_type');
        
        $requisitionListItems = $this->collection->
            addFieldToFilter('requisition_list_id', ['in' => $requisitionListIds]);
        
        $result = [];
        if ($rlType == 'seasonal') {
            $adminRlList = $this->adminRlItems->getOrderHistory($customerId);
            $orderedSkus = $adminRlList['ordered_skus'];
            if (!empty($orderedSkus)) {
                foreach ($orderedSkus as $sku => $value) {
                    $requesitionItem = [];
                    try {
                        $seasonalRl = $this->adminRlItems->seasonalRlQty($customerId, $sku);
                        $product = $this->productRepository->get($sku);
                        if ($product->getIsPlp() && $product->getStatus() != 2) {
                            $productData = $product->getData();
                            $requesitionItem['subtotal'] = $seasonalRl * $product->getFinalPrice();
                            $requesitionItem['quantity'] = $seasonalRl;
                            $productData['model'] = $product;
                            $requesitionItem['adminitemsdata'] = $productData;
                            $result[] = $requesitionItem;
                        }
                    } catch (NoSuchEntityException $e) {
                        $this->logger->info($e->getMessage());
                    }
                }
            }
        } elseif ($rlType == 'normal') {
            $adminRlList = $this->adminRlItems->getOrderHistory($customerId);
            $orderedSkus = $adminRlList['ordered_skus'];
            if (!empty($orderedSkus)) {
                foreach ($orderedSkus as $sku => $value) {
                    $requesitionItem = [];
                    try {
                        $normalRl = $this->adminRlItems->normalRlQty($customerId, $sku);
                        $product = $this->productRepository->get($sku);
                        if ($product->getIsPlp() && $product->getStatus() != 2) {
                            $productData = $product->getData();
                            $requesitionItem['subtotal'] = $normalRl * $product->getFinalPrice();
                            $requesitionItem['quantity'] = $normalRl;
                            $productData['model'] = $product;
                            $requesitionItem['adminitemsdata'] = $productData;
                            $result[] = $requesitionItem;
                        }
                    } catch (NoSuchEntityException $e) {
                        $this->logger->info($e->getMessage());
                    }
                }
            }
        } else {
            $data = $requisitionListItems->getData();
            foreach ($data as $requisitionListItem) {
                $requesitionItem = [];
                try {
                    $product = $this->productRepository->getById($requisitionListItem['product_id']);
                    if ($product->getIsPlp() && $product->getStatus() != 2) {
                        $quantity = $requisitionListItem['qty'];
                        $productData = $product->getData();
                        $price = $product->getPrice();
                        $subtotal = ($price * $quantity);
                        $productId = $product->getId();
                        $requesitionItem['subtotal'] = $subtotal;
                        $requesitionItem['quantity'] = $quantity;
                        $productData['model'] = $product;
                        $requesitionItem['adminitemsdata'] = $productData;
                        $result[] = $requesitionItem;
                    }
                } catch (NoSuchEntityException $e) {
                    $this->logger->info($e->getMessage());
                }
                
            }
        }
        
        return $result;
    }
}
