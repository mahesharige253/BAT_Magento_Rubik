<?php

declare(strict_types=1);

namespace Bat\VirtualBank\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\VirtualBank\Model\Resolver\DataProvider\VirtualBankListDataProvider;

/**
 * @class VirtualBankList
 * Resolver class for VirtualBankList
 */
class VirtualBankList implements ResolverInterface
{
    /**
     * @var VirtualBankListDataProvider
     */
    private VirtualBankListDataProvider $virtualBankListDataProvider;

    /**
     * @param VirtualBankListDataProvider $virtualBankListDataProvider
     */
    public function __construct(
        VirtualBankListDataProvider $virtualBankListDataProvider
    ) {
        $this->virtualBankListDataProvider = $virtualBankListDataProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        return $this->getVirtualBankList();
    }

    /**
     * Return Banks list
     *
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getVirtualBankList(): array
    {
        $result = $this->virtualBankListDataProvider->getVirtualBankList();
        if (empty($result)) {
            throw new GraphQlNoSuchEntityException(__('No Banks are available at this moment'));
        }
        return ['bank_details' => $result];
    }
}
