<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\RequisitionList\Model\Resolver\RequisitionList;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Bat\RequisitionList\Model\ResourceModel\RequisitionListItemAdmin\CollectionFactory;

class GetAdminRequisitionList
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**

     * @var CollectionFactory
     */
    private $collection;

    /**

     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $collection
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CollectionFactory $collection
    ) {
        $this->productRepository = $productRepository;
        $this->collection = $collection;
    }

    /**
     * Get Admin Rl Product Function
     * 
     * @param int $requisitionListId
     * Getting AdminRL Product first name and total product count
     */
    public function getadminRequisitionProduct(int $requisitionListId): array
    {
        $requisitionListItems = $this->collection->create()
            ->addFieldToSelect('product_id')
            ->addFieldToFilter('requisition_list_id', $requisitionListId);
        if($requisitionListItems->getSize() > 0){
            $i = 0;
            $productName = '';
            foreach($requisitionListItems->getData() as $item){
                $product = $this->productRepository->getById($item['product_id']);
                if($product->getIsPlp() && $product->getStatus() != 2){
                    $i++;
                    if($i == 1){
                        $productName = $product->getName();
                    }
                }
            }
            return [
                        'name' => $productName,
                        'product_count' => $i
                    ];
        } else {
            return [
                'product_count' => 0
            ];
        }
       
    }
}
