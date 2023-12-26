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
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;

/**
 * DecryptData  resolver
 */
class DiscountMessage implements ResolverInterface
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
        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $itemMessage = '';
        $discountMessage = $this->getDiscountMessage->getDiscountMessage($customerId);
        $itemdiscountMessage[] = $this->getDiscountMessage->getItemMessage($customerId);
        if (!empty($itemdiscountMessage[0])) {
            foreach ($itemdiscountMessage[0] as $itemDiscount) {
                if ($itemDiscount == '{"context":"benefit"}') {
                    $i = array_search('{"context":"benefit"}', $itemdiscountMessage[0]);
                    unset($itemdiscountMessage[0][$i]);
                }
            }
            if (!empty($itemdiscountMessage[0])) {
                return $itemdiscountMessage[0];
            }
            return $itemMessage = ['{"context":"benefit"}'];
        }
        return $discountMessage;
    }
}