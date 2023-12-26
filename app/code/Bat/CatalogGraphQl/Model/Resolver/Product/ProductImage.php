<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductImage implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Construct method
     *
     * @param ProductRepository $productRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
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
        $customAttributeValue = '';
        $productImageDecode = [];
        $product = $value['model'];
        
        try {
            $product = $this->productRepository->get($product['sku']);
            $customAttributeValue = $product->getData('images');
            if ($customAttributeValue != '') {
                $productImageDecode = json_decode($customAttributeValue);
            }
            if (!empty($productImageDecode) && is_array($productImageDecode)) {
                $data = get_object_vars($productImageDecode[0]);
                $imageEncodeUrl = base64_encode($data['fileURL']);
            }
            if ($imageEncodeUrl == '') {
                $mediaUrl = $this->storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $imageEncodeUrl = base64_encode($mediaUrl.'catalog/product/placeholder/default.png');
            }
        } catch (NoSuchEntityException $e) {
            $imageEncodeUrl = '';
        } catch(Exception $e) {
            $imageEncodeUrl = '';
        }
        return $imageEncodeUrl;
    }
}
