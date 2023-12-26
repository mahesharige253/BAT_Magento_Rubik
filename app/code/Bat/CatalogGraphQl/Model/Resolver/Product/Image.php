<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Helper\Image as CatalogHelperImage;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Image implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CatalogHelperImage
     */
    protected $imageHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Construct method
     *
     * @param ProductRepository $productRepository
     * @param StoreManagerInterface $storeManager
     * @param CatalogHelperImage $imageHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ProductRepository $productRepository,
        StoreManagerInterface $storeManager,
        CatalogHelperImage $imageHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
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
        $imageEncodeUrl = '';
        $product = $value['model'];

        if (!empty($product->getImage()) && $product->getImage() != 'no_selection') {
            $width = $this->scopeConfig->getValue(
                'bat_catalog_section/general/product_detail_page_image_width',
                ScopeInterface::SCOPE_STORE
            );
            $height = $this->scopeConfig->getValue(
                'bat_catalog_section/general/product_detail_page_image_height',
                ScopeInterface::SCOPE_STORE
            );

            $productImageUrl = $this->imageHelper->init($product, 'thumbnail')
            ->setImageFile($product->getImage())
            ->resize($width, $height)
            ->getUrl();
            $imageEncodeUrl = base64_encode($productImageUrl);
        }

        return $imageEncodeUrl;
    }

    /**
     * Get Media Url
     */
    public function getMediaUrl()
    {
        $prodPath = 'catalog/product';
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$prodPath;
    }
}
