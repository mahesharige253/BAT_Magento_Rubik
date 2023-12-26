<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\Cms\Model\Resolver;

use Bat\Cms\Model\Resolver\DataProvider\CmsPage as PageDataProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * CMS page field resolver, used for GraphQL request processing
 */
class GetCmsPage implements ResolverInterface
{
    /**
     * @var PageDataProvider
     */
    private $pageDataProvider;

    /**
     * @param PageDataProvider $pageDataProvider
     */
    public function __construct(
        PageDataProvider $pageDataProvider
    ) {
        $this->pageDataProvider = $pageDataProvider;
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
        if (!isset($args['identifier'])) {
            throw new GraphQlInputException(__('"Page identifier should be specified'));
        }

        $pageData = [];

        try {
            if (isset($args['identifier'])) {
                $pageData = $this->pageDataProvider->getDataByPageIdentifier(
                    (string)$args['identifier'],
                    (int)$context->getExtensionAttributes()->getStore()->getId()
                );
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $pageData;
    }
}
