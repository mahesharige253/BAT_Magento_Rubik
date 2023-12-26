<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\RequisitionList\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Bat\RequisitionList\Model\RequisitionListAdminFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\RequisitionList\Model\Resolver\RequisitionList\GetAdminRequisitionList;
use Bat\RequisitionList\Model\NormalSeasonalOtherRlItems;

/**
 * RequisitionList Resolver
 */
class RequisitionList implements ResolverInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var GetAdminRequisitionList
     */
    private $getAdminRequisitionList;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RequisitionListAdminFactory
     */
    private $requisitionListAdminFactory;

    /**
     * @var NormalSeasonalOtherRlItems
     */
    protected $adminRlItems;

    /**
     * @param ModuleConfig $moduleConfig
     * @param GetAdminRequisitionList $getAdminRequisitionList
     * @param ScopeConfigInterface $scopeConfig
     * @param RequisitionListAdminFactory $requisitionListAdminFactory
     * @param NormalSeasonalOtherRlItems $adminRlItems
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        GetAdminRequisitionList $getAdminRequisitionList,
        ScopeConfigInterface $scopeConfig,
        RequisitionListAdminFactory $requisitionListAdminFactory,
        NormalSeasonalOtherRlItems $adminRlItems
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->getAdminRequisitionList = $getAdminRequisitionList;
        $this->scopeConfig = $scopeConfig;
        $this->requisitionListAdminFactory = $requisitionListAdminFactory;
        $this->adminRlItems = $adminRlItems;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed|Value
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->moduleConfig->isActive()) {
            throw new GraphQlInputException(__('Requisition List feature is not available.'));
        }
        $customerId = $context->getUserId();
        $collection = $this->requisitionListAdminFactory->create()->getCollection();
        $collection->addFieldToFilter('status', ['eq'=>1]);

        $normalSeasonalRl = $this->adminRlItems->isAllowedNormalSeasonalRl($customerId);
             
        if (!$normalSeasonalRl['seasonal']) {
            $collection->addFieldToFilter('rl_type', ['neq'=>'seasonal']);
        }
       
        if (!$normalSeasonalRl['normal']) {
            $collection->addFieldToFilter('rl_type', ['neq'=>'normal']);
        }
       
        $records = $collection->getData();
        $adminLimit = $this->scopeConfig->getValue(
            'requisitionlist_bat/requisitionlist/requisitionlist_admin',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );

        if ($collection->getSize() > 0) {
            $adminRl = [];
            $i = 0;
            foreach ($records as $data) {
                $requisitionListId = (int) $data['entity_id'];
                $productName = $this->adminRlItems->getProductName($requisitionListId, $data['rl_type'], $customerId);
                $productCount = $this->adminRlItems->getItemsCount($requisitionListId, $data['rl_type'], $customerId);
                if ($productCount > 0 || $data['rl_type'] == 'bestseller') {
                    $arr = [
                        'uid' => $data['entity_id'],
                        'name' => $data['name'],
                        'first_product_name' => $productName,
                        'product_count' => $productCount,
                        'rl_type' => $data['rl_type'],
                    ];
                    $adminRl['items'][] = $arr;
                    $i++;
                }
            }
            $total = [
                'total_rl_count' => $i,
                'admin_max_limit' => $adminLimit
            ];
            $adminRl['total'] = $total;
            return $adminRl;
        } else {
            throw new GraphQlNoSuchEntityException(__('There is no Admin Requisition list '));
        }
    }
}
