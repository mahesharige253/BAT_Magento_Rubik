<?php

declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class CreateNewProduct implements ResolverInterface
{
    /**
     * @var \Magento\Catalog\Api\Data\ProductInterfaceFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * Construct method
     *
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Filesystem $filesystem
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        Filesystem $filesystem,
        CollectionFactory $productCollectionFactory
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->filesystem = $filesystem;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Resolver method to create product
     *
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     * @throws GraphQlInputException
     */

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $message = '';
        /* check if sku is passed in graphql and validate the same */
        if (!isset($args['input']['sku'])) {
            throw new GraphQlInputException(__('SKU value should be specified'));
        } elseif (isset($args['input']['sku']) && ($args['input']['sku'] == '')) {
            throw new GraphQlInputException(__('SKU value is required'));
        }

        /* check if name is passed in graphql and validate the same */
        if (!isset($args['input']['name'])) {
            throw new GraphQlInputException(__('Product Name value should be specified'));
        } elseif (isset($args['input']['name']) && ($args['input']['sku'] == '')) {
            throw new GraphQlInputException(__('Product Name value is required'));
        }

        /* check if price is passed in graphql and validate the same */
        if (!isset($args['input']['price'])) {
            throw new GraphQlInputException(__('Price should be specified'));
        } elseif (isset($args['input']['price']) && ($args['input']['sku'] == '')) {
            throw new GraphQlInputException(__('Price is required'));
        }

        /* check if weight is passed in graphql and validate the same */
        if (!isset($args['input']['weight'])) {
            throw new GraphQlInputException(__('Product Weight should be specified'));
        } elseif (isset($args['input']['weight']) && ($args['input']['sku'] == '')) {
            throw new GraphQlInputException(__('Product Weight is required'));
        }

        /* check if quantity is passed in graphql and validate the same */
        if (!isset($args['input']['extension_attributes']['stock_item']['qty'])) {
            throw new GraphQlInputException(__('Product Weight should be specified'));
        } elseif (isset($args['input']['extension_attributes']['stock_item']['qty'])
            && ($args['input']['extension_attributes']['stock_item']['qty'] == '')) {
            throw new GraphQlInputException(__('Product Weight is required'));
        }

        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $this->productFactory->create();

        if ($product->getIdBySku($args['input']['sku'])) {
            throw new GraphQlInputException(__('This SKU already exist with other product'));
        }

        try {
            $urlKey = strtolower(str_replace(' ', '-', $args['input']['name']));
            $i = 0;
            $appended = '';
            $urlKeyVal = '';
            while ($i <= 10) {
                $urlKeyVal = $urlKey.$appended;
                $urlExist  = $this->getProductByUrl($urlKeyVal);
                if (empty($urlExist)) {
                    break;
                }
                $i++;
                $appended = '-'.$i;
            }
            $product->setUrlKey($urlKeyVal);
            $product->setSku($args['input']['sku']);
            $product->setName($args['input']['name']);
            if (isset($args['input']['pricetag_type'])) {
                $product->setPricetagType($args['input']['pricetag_type']);
                $product->setVisibility(1);
                $product->setStockData(
                    [
                        'use_config_manage_stock' => 0,
                        'manage_stock' => 0,
                        'is_in_stock' => 1,
                        'qty' => ''
                    ]
                );
                $product->setPrice(0);
            } else {
                $product->setVisibility(4);
                $product->setStockData(
                    [
                        'use_config_manage_stock' => 0,
                        'manage_stock' => 1,
                        'is_in_stock' => 1,
                        'qty' => $args['input']['extension_attributes']['stock_item']['qty']
                    ]
                );
                $product->setPrice($args['input']['price']);
            }
            $product->setAttributeSetId(4); // Default attribute set for products
            $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
            $product->setCustomAttribute('tax_class_id', 2); // 2 is the default tax class id
            $product->setWeight($args['input']['weight']);
            $product->setWebsiteIds([1]);
            $product->setStoreId(0);

            $categoryIds = explode(',', $args['input']['categoryIds']);
            $product->setCategoryIds($categoryIds);
            
            foreach ($args['input']['custom_attributes'] as $custom_attributes_k => $custom_attributes_v) {
                $attrCode = $this->productFactory->create()->getResource()
                            ->getAttribute($custom_attributes_v['attribute_code']);
                if ($attrCode->getFrontendInput() == 'select') {
                    $getId = $attrCode->getSource()->getOptionId($custom_attributes_v['value']);
                    if (empty($getId)) {
                        throw new GraphQlNoSuchEntityException(
                            __($custom_attributes_v['attribute_code'] .' option value is not corrects')
                        );
                    }
                }
                $attrOptionId = $this->getAttrOptIdByLabel(
                    $custom_attributes_v['attribute_code'],
                    $custom_attributes_v['value']
                );
                if ($attrOptionId == '') {
                    $attrOptionId = $custom_attributes_v['value'];
                }
                $product->setData($custom_attributes_v['attribute_code'], $attrOptionId);
            }

            $product = $this->productRepository->save($product);
            if ($product->getEntityId()) {
                $message = __('Product has been created successfully');

            }

            if (isset($args['input']['image']) && $args['input']['image'] !='') {
                $filesystem = $this->filesystem;
                $mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                $mediaPath = $mediaDirectory->getAbsolutePath();

                $imagePath = $mediaPath.'import/'.$args['input']['image']; // path of the image
                $product->addImageToMediaGallery($imagePath, ['image', 'small_image', 'thumbnail'], false, false);
            }
            $product->save();

            return [
                    'status' => true,
                    'message' => $message
                ];
        } catch (\Exception $e) {
            throw new GraphQlNoSuchEntityException(
                __('The product was unable to be saved. '.$e->getMessage()),
                $e
            );
        }
    }

    /**
     * Pass here attribute code and option label as param
     *
     * @param String $attrCode
     * @param String $optLabel
     */
    public function getAttrOptIdByLabel($attrCode, $optLabel)
    {
        $product = $this->productFactory->create();
        $isAttrExist = $product->getResource()->getAttribute($attrCode); // Add here your attribute code
        $optId = '';
        if ($isAttrExist && $isAttrExist->usesSource()) {
                $optId = $isAttrExist->getSource()->getOptionId($optLabel);
        }
        return $optId;
    }

    /**
     * Get Product by URL
     *
     * @param String $urlKey
     */
    public function getProductByUrl($urlKey)
    {
        $collectionNewProduct = $this->productCollectionFactory->create();
        $collectionNewProduct->addAttributeToSelect('entity_id');
        $collectionNewProduct->addAttributeToFilter('url_key', $urlKey);
        $productResult = $collectionNewProduct->getData();
        return $productResult;
    }
}
