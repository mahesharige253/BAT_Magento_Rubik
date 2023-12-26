<?php

namespace Bat\RequisitionList\Plugin;

use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionListGraphQl\Model\RequisitionList\Item\AddItemsToRequisitionList;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\RequisitionListGraphQl\Model\RequisitionList\DeleteItems as DeleteItemsModel;

class AddProductRlQuantityValidator
{

    /**
     * @var RequisitionListRepository
     */
    private $repository;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var GetDiscountMessage
     */
    private $getDiscountMessage;

   /**
     * @var DeleteItemsModel
     */
    private $deleteRequisitionListItemsForUser;

    /**
     * @param RequisitionListRepository $repository
     * @param Data $helper
     * @param GetDiscountMessage $getDiscountMessage
     * @param DeleteItemsModel $deleteRequisitionListItemsForUser
     */
    public function __construct(
        RequisitionListRepository $repository,
        Data $helper,
        GetDiscountMessage $getDiscountMessage,
        DeleteItemsModel $deleteRequisitionListItemsForUser
    ) {
        $this->repository = $repository;
        $this->helper = $helper;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->deleteRequisitionListItemsForUser = $deleteRequisitionListItemsForUser;
    }

    /**
     * Before Add product to RL
     *
     * @param AddItemsToRequisitionList $subject
     * @param RequisitionListInterface $requisitionList
     * @param array $items
     * @return array
     */
    public function beforeExecute(
        AddItemsToRequisitionList $subject,
        RequisitionListInterface $requisitionList,
        array $items
    ): array {
        $requisitionListId = (int)$requisitionList->getId();
        $totalReceivedQty = 0;
        foreach ($items as $item) {
            $totalReceivedQty += $item->getQuantity();
        }
        $ListData = $this->repository->get($requisitionListId);
        $existingQty = 0;
        $requisitionListItemsId = [];
        if ($ListData->getItems()) {
            foreach ($ListData->getItems() as $item) {
                if($this->getDiscountMessage->getIsProductEnable($item->getSku()) != 1){
                $requisitionListItemsId[] = $item->getId();
                $this->deleteRequisitionListItemsForUser->execute($requisitionListItemsId, $requisitionListId);
                }
                else{
                    $existingQty += (int)$item->getQty();
                }
            }
        }
        $totalAvailQty = $totalReceivedQty + $existingQty;
        if ($this->helper->getMaximumCartQty() < $totalAvailQty) {
            throw new GraphQlInputException(
                __('Maximum RL quantity are allowed:'.$this->helper->getMaximumCartQty().' or less than.')
            );
        }

        return [$requisitionList, $items];
    }
}
