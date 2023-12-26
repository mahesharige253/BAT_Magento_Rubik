<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Category implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Construct method
     *
     * @param CategoryFactory $categoryFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_categoryFactory = $categoryFactory;
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
        $categoryData = [];
        $categoryVal = '';
        $suffix = $this->scopeConfig->getValue('catalog/seo/product_url_suffix', ScopeInterface::SCOPE_STORE);
        $product = $value['model'];
        $catIds = $product->getCategoryIds();
        foreach ($catIds as $catId) {
            $category = $this->_categoryFactory->create()->load($catId);
            $categoryData[] = ['url' => '/category/'.$category->getUrlPath().$suffix, 'label'=> $category->getName()];

        }
        $categoryVal = $categoryData;
        return $categoryVal;
    }
}
