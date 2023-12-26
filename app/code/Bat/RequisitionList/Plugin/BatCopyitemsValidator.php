<?php

namespace Bat\RequisitionList\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList\CopyItems;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\RequisitionListGraphQl\Model\RequisitionList\DeleteItems as DeleteItemsModel;

class BatCopyitemsValidator
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
     * Copy Items Between RL
     *
     * @param CopyItems $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        CopyItems $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {

        $itemIds = array_map(
            function ($id) {
                return $this->idEncoder->decode($id);
            },
            $args['requisitionListItem']['requisitionListItemUids']
        );
        $destRequisitionListId = (int) $this->idEncoder->decode($args['destinationRequisitionListUid']);
        $sourceRequisitionListId = (int) $this->idEncoder->decode($args['sourceRequisitionListUid']);
        $RlItemsList = $this->getRequisitionListBySourceAndItemsId($itemIds, $sourceRequisitionListId);
        $receivingQty = 0;
        $sourcerequisitionListItemsId = [];
        foreach ($RlItemsList as $item) {
            if ($this->getDiscountMessage->getIsProductEnable($item->getSku()) != 1) {
                $sourcerequisitionListItemsId[] = $item->getId();
                $this->deleteRequisitionListItemsForUser->execute($sourcerequisitionListItemsId, $sourceRequisitionListId);
            } else {
                $receivingQty += $item->getQty();
            }
        }

        $ListData = $this->repository->get($destRequisitionListId);
        $existingQty = 0;
        $destrequisitionListItemsId = [];
        if ($ListData->getItems()) {
            foreach ($ListData->getItems() as $item) {
                if ($this->getDiscountMessage->getIsProductEnable($item->getSku()) != 1) {
                    $destrequisitionListItemsId[] = $item->getId();
                    $this->deleteRequisitionListItemsForUser->execute($destrequisitionListItemsId, $destRequisitionListId);
                } else {
                    $existingQty += (int) $item->getQty();
                }
            }
        }
        $availQuantity = $existingQty + $receivingQty;
        if ($this->helper->getMaximumCartQty() < $availQuantity) {
            throw new GraphQlInputException(
                __('Maximum RL quantity are allowed:' . $this->helper->getMaximumCartQty() . ' or less than.')
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
