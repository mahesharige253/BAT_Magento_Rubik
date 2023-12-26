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
use Bat\Integration\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;

/**
 * DecryptData  resolver
 */
class ItemMessage implements ResolverInterface
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
     * @var Data
     */
    private $data;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var GetDiscountMessage
     */
    private $getDiscountMessage;

    /**
     *
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Data $data
     * @param GetCustomer $getCustomer
     * @param GetDiscountMessage $getDiscountMessage
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Data $data,
        GetCustomer $getCustomer,
        GetDiscountMessage $getDiscountMessage
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->data = $data;
        $this->getCustomer = $getCustomer;
        $this->getDiscountMessage = $getDiscountMessage;
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
        $product = $value['model'];
        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $discountMessage[] = $this->getDiscountMessage->getItemMessage($customerId);
        $productMatched = [];
        $productMatched = array_keys($discountMessage[0]);
        if (!empty($productMatched)) {
            foreach ($productMatched as $productCondition) {
                if ($productCondition == $product->getSku()) {
                    return $discountMessage[0][$product->getsku()];
                }
            }
        }
    }
}
