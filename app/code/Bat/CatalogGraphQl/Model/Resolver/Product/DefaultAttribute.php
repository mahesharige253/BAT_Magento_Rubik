<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ProductRepository;

class DefaultAttribute implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     *
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * Construct method
     *
     * @param ProductRepository $productRepository
     */
    public function __construct(
        ProductRepository $productRepository
    ) {
        $this->_productRepository = $productRepository;
    }

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
        $defaultTextAttributeVal = '';
        $attributeLabel = '';
        $selectedAttributeVal = '';
        $product = $value['model'];
        $sku = $product->getSku();
        if($product->getStatus() == 1){
        $productData = $this->_productRepository->get($sku);
        $attributeCode = $productData->getBatDefaultAttribute();
        $attribute = $productData->getResource()->getAttribute($attributeCode);
        if ($attribute) {
            if (
                in_array(
                    $productData->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                    ['select']
                ) ||
                in_array(
                    $productData->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                    ['boolean']
                )
            ) {
                $selectedAttributeVal = $productData->getAttributeText($attributeCode);
                $attributeLabel = $productData->getResource()->getAttribute($attributeCode)->
                    getFrontendLabel();
            } else {
                $attributeLabel = $productData->getResource()->getAttribute($attributeCode)->
                    getFrontendLabel();
                $selectedAttributeVal = $productData->getData($attributeCode);
            }
        }
        if ($selectedAttributeVal != '') {
            $defaultTextAttributeVal = $attributeLabel . ': ' . $selectedAttributeVal;
        }
    }
        return $defaultTextAttributeVal;
    }
}
