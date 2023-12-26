<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Product Data resolver
 */
class ProductData implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    private $_productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     *
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        foreach ($collection as $product) {
            $productData = $product->getData();
            $sku = $product->getId();
            $productArray[$sku] = $productData;
            $productArray[$sku]['model'] = $product;
        }
        $result['items'] = $productArray;
        return $result;
    }
}
