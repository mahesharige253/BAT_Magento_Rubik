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
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionList\Model\Config as ModuleConfig;
use Bat\RequisitionList\Model\Resolver\RequisitionList\GetAdminRequisitionListItems;

/**
 * RequisitionList Resolver
 */
class AdminItems implements ResolverInterface
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var GetAdminRequisitionListItems
     */
    private $getAdminRequisitionListItems;

    /**
     * @param ModuleConfig $moduleConfig
     * @param GetAdminRequisitionListItems $getAdminRequisitionListItems
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        GetAdminRequisitionListItems $getAdminRequisitionListItems,
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->getAdminRequisitionListItems = $getAdminRequisitionListItems;
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
        $requisitionListIds[] = $args['requisition_list_id'];
        return $this->getAdminRequisitionListItems->getadminRequisitionListData($requisitionListIds, $customerId);
    }
}
