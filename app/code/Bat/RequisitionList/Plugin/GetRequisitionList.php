<?php

namespace Bat\RequisitionList\Plugin;

use Magento\RequisitionListGraphQl\Model\RequisitionList\GetRequisitionList as GetRequisitionListItem;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;

class GetRequisitionList
{
    /**
     * @var RequisitionListItems
     */
    private $itemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param RequisitionListItems $itemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        RequisitionListItems $itemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        $this->itemRepository = $itemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * AfterExecute plugin
     *
     * @param GetRequisitionListItem $subject
     * @param object $result
     */
    public function afterExecute(GetRequisitionListItem $subject, $result)
    {
        $array = [];
        foreach ($result['items'] as $key => $itemData) {
            $itemCount = $itemData['items_count'] = $this->getItemCounts([$key]);
            $array[$key] = $itemData;
        }
        $result['items'] = $array;
        return $result;
    }

    /**
     * Get Item Counts
     *
     * @param array $requisitionListIds
     * @return int
     */
    public function getItemCounts(array $requisitionListIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter("requisition_list_id", $requisitionListIds, "in")
            ->create();
        $requisitionListItems = $this->itemRepository->getList($searchCriteria)->getItems();
        $skus = $this->getItemsSkus($requisitionListItems);

        $searchCriteria = $this->searchCriteriaBuilder->addFilter("sku", $skus, "in")
            ->addFilter("status", 1, "eq")->addFilter("is_plp", 1, "eq")->create();
        $productList = $this->productRepository->getList($searchCriteria)->getItems();
        $productListData = $this->prepareProductList($productList);
        $i = 0;
        foreach ($requisitionListItems as $item) {
            if (array_key_exists($item->getSku(), $productListData)) {
                $i++;
            }
        }
        return $i;
    }

    /**
     * Prepare product list
     *
     * @param ProductInterface[] $productList
     * @return array
     */
    private function prepareProductList(array $productList)
    {
        $productListData = [];
        foreach ($productList as $product) {
            $productListData[$product->getSku()] = $product;
        }
        return $productListData;
    }

     /**
      * Get all sku
      *
      * @param ExtensibleDataInterface[] $requisitionListItems
      * @return array
      */
    private function getItemsSkus(array $requisitionListItems)
    {
        $skus = [];

        /** @var RequisitionListItemInterface  $item */
        foreach ($requisitionListItems as $item) {
            $skus[] = $item->getSku();
        }
        return $skus;
    }
}
