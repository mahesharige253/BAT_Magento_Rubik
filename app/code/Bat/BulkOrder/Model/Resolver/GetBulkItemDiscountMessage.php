<?php
declare(strict_types=1);

namespace Bat\BulkOrder\Model\Resolver;

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
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * DecryptData  resolver
 */
class GetBulkItemDiscountMessage implements ResolverInterface
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
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var CartDetails
     */
    protected $cartDetails;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     *
     * @param Data $data
     * @param GetCustomer $getCustomer
     * @param GetDiscountMessage $getDiscountMessage
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param CartDetails $cartDetails
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Data $data,
        GetCustomer $getCustomer,
        GetDiscountMessage $getDiscountMessage,
        QuoteCollectionFactory $quoteCollectionFactory,
        CartDetails $cartDetails,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->data = $data;
        $this->getCustomer = $getCustomer;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->cartDetails = $cartDetails;
        $this->customerRepository = $customerRepository;
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
        $cartItemId = $product->getId();
        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $customer = $this->customerRepository->getById($customerId);
        $parentOutletId = $customer->getCustomAttribute('outlet_id')->getValue();
        $quote = $this->quoteCollectionFactory->create()->
            addFieldToFilter('parent_outlet_id', $parentOutletId)->addFieldToFilter('is_active', 1);
        $dataDetails = $quote->getData();
        $quoteData = [];
        $inputs = [];
        foreach ($dataDetails as $dataDetail) {
            $quoteData['outlet_id'] = $dataDetail['outlet_id'];
            $quoteId = $dataDetail['entity_id'];
            $entityId = (int) $quoteId;
            $maskedCartId = $this->cartDetails->getQuoteMaskedIdByEntityId($entityId);
            $quoteData['maskedCartId'] = $maskedCartId;
            $inputs[] = $quoteData;
        }
        foreach ($inputs as $input) {
            $outletIds = $input['outlet_id'];
            $userId = $this->cartDetails->getCustomerIdsByCustomAttribute($outletIds);
            $currentUserId[] = (int) $userId[0];
        }
        foreach ($currentUserId as $customerId) {
            $discountMessage = [];
            $cartItemsId = [];
            $discountMessage[] = $this->getDiscountMessage->getItemMessage($customerId);
            if (!empty($discountMessage[0])) {
                $cartItemsId = $this->cartDetails->getCartItemsId($customerId);
                if (in_array($cartItemId, $cartItemsId)) {
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
        }
    }
}