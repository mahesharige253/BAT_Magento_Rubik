<?php

declare(strict_types=1);

namespace Bat\Rma\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\Rma\Model\DataProvider\AccountClosureProductListDataProvider;

/**
 * @class AccountClosureProductList
 * Resolver class for Account Closure Product List
 */
class AccountClosureProductList implements ResolverInterface
{
    /**
     * @var AccountClosureProductListDataProvider
     */
    private AccountClosureProductListDataProvider $accountClosureProductListDataProvider;

    /**
     * @param AccountClosureProductListDataProvider $accountClosureProductListDataProvider
     */
    public function __construct(
        AccountClosureProductListDataProvider $accountClosureProductListDataProvider
    ) {
        $this->accountClosureProductListDataProvider = $accountClosureProductListDataProvider;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return \Magento\Framework\GraphQl\Query\Resolver\Value|mixed|void
     * @throws GraphQlAuthorizationException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__("The current customer isn\'t authorized."));
        } else {
            $currentPage = 1;
            if (isset($args['current_page'])) {
                $currentPage = $args['current_page'];
            }
            $pageSize = 20;
            if (isset($args['page_size'])) {
                $pageSize = $args['page_size'];
            }
            return $this->accountClosureProductListDataProvider->getAccountClosureProductList($currentPage, $pageSize);
        }
    }
}
