<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

class Price implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * Resolve method
     *
     * @param Field $field
     * @param Context $context
     * @param ResolveInfo $info
     * @param Array $value
     * @param Array $args
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $product = $value['model'];
        $price = (int)$product->getPrice();
        return $price;
    }
}
