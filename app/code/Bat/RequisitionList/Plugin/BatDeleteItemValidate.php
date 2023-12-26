<?php

namespace Bat\RequisitionList\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList\DeleteItems;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\RequisitionListGraphQl\Model\RequisitionList\DeleteItems as DeleteItemsModel;

class BatDeleteItemValidate
{
    
    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @var RequisitionListRepository
     */
    private $repository;

    /**
     * @var Data
     */
    private $helper;

     /**
      * @var SearchCriteriaBuilder
      */
    private $searchCriteriaBuilder;

     /**
      * @var RequisitionListItems
      */
    private $requisitionListItems;

    /**
     * @var GetDiscountMessage
     */
    private $getDiscountMessage;

    /**
     * @var DeleteItemsModel
     */
    private $deleteRequisitionListItemsForUser;

    /**
     * @param IdEncoder $idEncoder
     * @param RequisitionListRepository $repository
     * @param Data $helper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequisitionListItems $requisitionListItems
     * @param GetDiscountMessage $getDiscountMessage
     * @param DeleteItemsModel $deleteRequisitionListItemsForUser
     */
    public function __construct(
        IdEncoder $idEncoder,
        RequisitionListRepository $repository,
        Data $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequisitionListItems $requisitionListItems,
        GetDiscountMessage $getDiscountMessage,
        DeleteItemsModel $deleteRequisitionListItemsForUser
    ) {
        $this->idEncoder = $idEncoder;
        $this->repository = $repository;
        $this->helper = $helper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requisitionListItems = $requisitionListItems;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->deleteRequisitionListItemsForUser = $deleteRequisitionListItemsForUser;
    }

    /**
     * Before remove product from RL
     *
     * @param DeleteItems $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        DeleteItems $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $requisitionListItemsId = array_map(
            function ($id) {
                return $this->idEncoder->decode($id);
            },
            $args['requisitionListItemUids']
        );
        $requisitionListId = (int)$this->idEncoder->decode($args['requisitionListUid']);
        $RlItemsList = $this->getRequisitionListBySourceAndItemsId($requisitionListItemsId, $requisitionListId);
        $receivingQty = 0;
        foreach ($RlItemsList as $item) {
            $receivingQty += $item->getQty();
        }

        $ListData = $this->repository->get($requisitionListId);
        $existingQty = 0;
        $requisitionListItemsId = [];
        if ($ListData->getItems()) {
            foreach ($ListData->getItems() as $item) {
                if ($this->getDiscountMessage->getIsProductEnable($item->getSku()) != 1) {
                    $requisitionListItemsId[] = $item->getId();
                    $this->deleteRequisitionListItemsForUser->execute($requisitionListItemsId, $requisitionListId);
                } else {
                    $existingQty += (int)$item->getQty();
                }
            }
        }
        
        $availQuantity = $existingQty - $receivingQty;

        if ($availQuantity < $this->helper->getMinimumCartQty() && ($availQuantity != 0)) {
            throw new GraphQlInputException(
                __('Minimum RL quantity are required:'.$this->helper->getMinimumCartQty())
            );
        }
        
        return [$field, $context, $info, $value, $args];
    }

    /**
     * Get requisition list by source id and item ids
     *
     * @param array $itemIds
     * @param int $sourceId
     * @return array
     */
    private function getRequisitionListBySourceAndItemsId(array $itemIds, int $sourceId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('requisition_list_id', $sourceId, 'eq')
            ->addFilter('item_id', $itemIds, 'in')
            ->create();
        return $this->requisitionListItems->getList($searchCriteria)->getItems();
    }
}
