<?php

namespace Bat\RequisitionList\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionListGraphQl\Model\Resolver\RequisitionList\AddToCart;
use Bat\GetCartGraphQl\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;
use Magento\Checkout\Model\Session;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\RequisitionListGraphQl\Model\RequisitionList\DeleteItems as DeleteItemsModel;

class AddCartProductRlQuantityValidator
{
    /**
     * @var IdEncoder
     */
    private $idEncoder;

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
     * @var Session
     */
    private $_session;

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
     * @param Data $helper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequisitionListItems $requisitionListItems
     * @param Session $session
     * @param GetDiscountMessage $getDiscountMessage
     * @param DeleteItemsModel $deleteRequisitionListItemsForUser
     */
    public function __construct(
        IdEncoder $idEncoder,
        Data $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequisitionListItems $requisitionListItems,
        Session $session,
        GetDiscountMessage $getDiscountMessage,
        DeleteItemsModel $deleteRequisitionListItemsForUser
    ) {
        $this->idEncoder = $idEncoder;
        $this->helper = $helper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->requisitionListItems = $requisitionListItems;
        $this->_session = $session;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->deleteRequisitionListItemsForUser = $deleteRequisitionListItemsForUser;
    }

    /**
     * Before remove product from RL
     *
     * @param AddToCart $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        AddToCart $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $requisitionListUid = (int) $this->idEncoder->decode($args['requisitionListUid']);
        $requisitionListItemsId = $args['requisitionListItemUids'];
        foreach ($requisitionListItemsId as $itemsId) {
            $itemid[] = (int) $this->idEncoder->decode($itemsId);
        }
        $items = $this->_session->getQuote()->getAllVisibleItems();
        $existingQty = 0;
        foreach ($items as $item) {
            $existingQty += $item->getQty();
        }
        $rlItemsList = $this->getRequisitionListBySourceAndItemsId($itemid, $requisitionListUid);
        $availableQty = 0;
        $requisitionListItemsId = [];
        foreach ($rlItemsList as $item) {
            if($this->getDiscountMessage->getIsProductEnable($item->getSku()) != 1){
                $requisitionListItemsId[] = $item->getId();
                $this->deleteRequisitionListItemsForUser->execute($requisitionListItemsId, $requisitionListUid);
                }
                else{
                    $availableQty += $item->getQty();
                }
        }
        $finalQuantity = $existingQty + $availableQty;

        if ($this->helper->getMaximumCartQty() < $finalQuantity) {
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
