<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ProductUrl implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Construct method
     *
     * @param ProductRepository $productRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ProductRepository $productRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
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
        $getUrlKey = '';
        $attributeVal = '';
        $product = $value['model'];
        $suffix = $this->scopeConfig->getValue('catalog/seo/product_url_suffix', ScopeInterface::SCOPE_STORE);
        $productData = $this->_productRepository->get($product->getSku());
        $getUrlKey = '/product/'.$productData->getUrlKey().$suffix;

        return $getUrlKey;
    }
}
