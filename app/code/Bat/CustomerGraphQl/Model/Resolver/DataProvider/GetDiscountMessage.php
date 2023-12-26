<?php

namespace Bat\CustomerGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\CustomerSegment\Model\Customer;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;
use Bat\RequisitionList\Helper\Data as RequisitionListData;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\SalesRule\Model\Rule\CustomerFactory as RuleCustomerFactory;

class GetDiscountMessage
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepositoryInterface;

    /**
     * @var SessionFactory
     */
    protected $sessionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepositoryInterface;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var SerializerInterface
     */
    protected $serializerInterface;

    /**
     * @var CollectionFactory
     */
    protected $segmentCollectionFactory;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CartDetails
     */
    protected $cartDetails;

    /**
    * @var ProductRepositoryInterface
    */
    protected $productRepositoryInterface;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RequisitionListData
     */
    protected $requisitionListData;

     /**
     * @var RuleCustomerFactory
     */
    protected $ruleCustomerFactory;

    /**
     * Construct method
     *
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param SessionFactory $sessionFactory
     * @param QuoteFactory $quoteFactory
     * @param SerializerInterface $serializerInterface
     * @param RuleFactory $ruleFactory
     * @param RuleRepositoryInterface $ruleRepositoryInterface
     * @param Quote $quote
     * @param CollectionFactory $segmentCollectionFactory
     * @param Cart $cart
     * @param CustomerFactory $customerFactory
     * @param Customer $customer
     * @param ProductRepository $productRepository
     * @param Data $helperData
     * @param StoreManagerInterface $storeManager
     * @param CartDetails $cartDetails
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param Product $product
     * @param LoggerInterface $logger
     * @param RequisitionListData $requisitionListData
     * @param RuleCustomerFactory $ruleCustomerFactory
     */
    public function __construct(
        CartRepositoryInterface $cartRepositoryInterface,
        SessionFactory $sessionFactory,
        QuoteFactory $quoteFactory,
        SerializerInterface $serializerInterface,
        RuleFactory $ruleFactory,
        RuleRepositoryInterface $ruleRepositoryInterface,
        Quote $quote,
        CollectionFactory $segmentCollectionFactory,
        Cart $cart,
        CustomerFactory $customerFactory,
        Customer $customer,
        ProductRepository $productRepository,
        Data $helperData,
        StoreManagerInterface $storeManager,
        CartDetails $cartDetails,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepositoryInterface,
        Product $product,
        LoggerInterface $logger,
        RequisitionListData $requisitionListData,
        RuleCustomerFactory $ruleCustomerFactory
    ) {
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->sessionFactory = $sessionFactory;
        $this->quoteFactory = $quoteFactory;
        $this->ruleFactory = $ruleFactory;
        $this->ruleRepositoryInterface = $ruleRepositoryInterface;
        $this->quote = $quote;
        $this->serializerInterface = $serializerInterface;
        $this->segmentCollectionFactory = $segmentCollectionFactory;
        $this->cart = $cart;
        $this->customerFactory = $customerFactory;
        $this->customer = $customer;
        $this->productRepository = $productRepository;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;
        $this->cartDetails = $cartDetails;
        $this->customerRepository = $customerRepository;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->product = $product;
        $this->logger = $logger;
        $this->requisitionListData = $requisitionListData;
        $this->ruleCustomerFactory = $ruleCustomerFactory;
    }

    /**
     * Get B2B Discount Message

     * @param int $customerId
     */
    public function getDiscountMessage($customerId)
    {
        $customerSegments = [];
        $appliedRuleIds = [];
        $ruleData = [];
        $discountMessage = [];
        $firstOrderRL = '';
        $cartItems = $this->getCartItems($customerId);
        $customerGroup = $this->getCustomerGroup($customerId);
        $websiteID = $this->storeManager->getStore()->getWebsiteId();
        $customerSegments = $this->customer->getCustomerSegmentIdsForWebsite($customerId, $websiteID);
        $objrules = $this->ruleFactory->create();
        $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
        $appliedruleid = $quote->getAppliedRuleIds();
        $firstOrderRL = $this->requisitionListData->getFirstOrderRL();

        if (!empty($appliedruleid)) {
            $appliedRuleIds = explode(',', $appliedruleid);
        }
        $rules[] = $objrules->getCollection()->addFieldToFilter("is_active", ['eq' => "1"]);
        foreach ($rules as $cartRules) {
            $data = $cartRules->getData();
        }
        $applied = false;
        foreach ($data as $ruleData) {
            $ruleCustomerSegmentID = [];
            if ($applied == false) {
                if (isset($ruleData['discount_type_rule']) && $ruleData['discount_type_rule'] == 1 && !empty($cartItems)) {
                    $dataToEncode = $ruleData['conditions_serialized'];
                    $conditions = $this->serializerInterface->unserialize($dataToEncode);
                    foreach ($conditions['conditions'] as $condition) {
                        if ($condition['type'] == 'Magento\CustomerSegment\Model\Segment\Condition\Segment') {
                            $ruleCustomerSegmentID = explode(',', $condition['value']);
                        }
                        $cartPriceRule = $this->ruleFactory->create()->load($ruleData['rule_id']);
                        $customerGroupIds = $cartPriceRule->getCustomerGroupIds();
                        foreach ($customerGroupIds as $customerGroupId) {
                            if ($customerGroupId == $customerGroup) {
                                foreach ($ruleCustomerSegmentID as $ruleSegmentId) {
                                    if (in_array($ruleSegmentId, $customerSegments)) {
                                        if (!empty($cartItems) && in_array($ruleData['rule_id'], $appliedRuleIds) && $this->cartDetails->getDiscountData($quote) != 0) {
                                            $applied = true;
                                            return $discountMessage = ['{"context":"benefit"}'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($applied == false) {
                if (isset($ruleData['discount_type_rule']) && $ruleData['discount_type_rule'] == 2 && !empty($cartItems)) {
                    $ruleCustomerSegmentID = [];
                    $dataToEncode = $ruleData['conditions_serialized'];
                    if($this->checkCustomerEligibility($customerId,$ruleData['rule_id']) == 1){
                    $conditions = $this->serializerInterface->unserialize($dataToEncode);
                    if ($this->isMigrated($customerId) != 1) {
                        foreach ($conditions['conditions'] as $condition) {
                            if ($condition['type'] == 'Magento\CustomerSegment\Model\Segment\Condition\Segment') {
                                $ruleCustomerSegmentID = explode(',', $condition['value']);
                            }
                        }
                            foreach ($ruleCustomerSegmentID as $ruleSegmentId) {
                                if (in_array($ruleSegmentId, $customerSegments)) {
                                    $rowTotal = '';
                                    foreach ($conditions['conditions'] as $condition) {
                                    if ($condition['type'] == 'Magento\SalesRule\Model\Rule\Condition\Product\Found') {
                                        foreach ($condition['conditions'] as $product) {
                                            if ($product['type'] == 'Magento\SalesRule\Model\Rule\Condition\Product' && $product['attribute'] == 'sku') {
                                                $condSku[] = $product['value'];
                                            } elseif ($product['type'] == 'Magento\SalesRule\Model\Rule\Condition\Product' && $product['attribute'] == 'quote_item_row_total') {
                                                $condRowTotal = $product['value'];
                                            }
                                        }
                                    }
                                }
                                        try {
                                            foreach($condSku as $condskus){
                                                if ($this->getIsProductEnable($condskus) != 1) {
                                                return $discountMessage[] = [];
                                            }
                                        }
                                                if (!empty($cartItems) && in_array($ruleData['rule_id'], $appliedRuleIds) &&
                                                        $this->cartDetails->getDiscountData($quote) != 0
                                                        ) {
                                                            $applied = true;
                                                            return $discountMessage = ['{"context":"benefit"}'];
                                                        }else {
                                                        $discountMessage[] = '{"context":"firstorder","discount":"' . $this->helperData->currency($ruleData['discount_amount'], false, false) . '","id":"' . $firstOrderRL . '"}';
                                                    }
                                        } catch (NoSuchEntityException $e) {
                                            $this->logger->info($e->getMessage());
                                        } catch (\Exception $e) {
                                            $this->logger->info($e->getMessage());
                                        }
                                    }
                                }
                            }
                        }
                    }
                if ($applied == false) {
                    if (isset($ruleData['discount_type_rule']) && $ruleData['discount_type_rule'] == 3 && !empty($cartItems)) {
                        $ruleCustomerSegmentID = [];
                        $cartPriceRule = $this->ruleFactory->create()->load($ruleData['rule_id']);
                        if($this->checkCustomerEligibility($customerId,$ruleData['rule_id']) == 1){
                        $customerGroupIds = $cartPriceRule->getCustomerGroupIds();
                        foreach ($customerGroupIds as $customerGroupId) {
                            if ($customerGroup == $customerGroupId) {
                                $dataToEncode = $ruleData['conditions_serialized'];
                                $conditions = $this->serializerInterface->unserialize($dataToEncode);
                                foreach ($conditions['conditions'] as $condition) {
                                    if ($condition['type'] == 'Magento\CustomerSegment\Model\Segment\Condition\Segment') {
                                        $ruleCustomerSegmentID = explode(',', $condition['value']);
                                    }
                                }
                                foreach ($ruleCustomerSegmentID as $ruleSegmentId) {
                                    if (in_array($ruleSegmentId, $customerSegments) && count($customerSegments) == 1) {
                                        foreach ($conditions['conditions'] as $condition) {
                                            if ($condition['type'] == 'Magento\SalesRule\Model\Rule\Condition\Address' &&
                                            $condition['attribute'] == 'base_subtotal'
                                            ) {
                                                    if (!empty($cartItems) && in_array($ruleData['rule_id'], $appliedRuleIds)
                                                    && $this->cartDetails->getDiscountData($quote) != 0
                                                    ) {
                                                        $applied = true;
                                                        return $discountMessage = ['{"context":"benefit"}'];
                                                    }
                                                 else {
                                                    return $discountMessage = ['{"context":"buymorecartitem","discount":"' . $this->helperData->currency($ruleData['discount_amount'], false, false) . '","price":"' . $this->helperData->currency($condition['value'], false, false) . '"}'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $discountMessage;
}

    /**
     * Get B2B Discount Message for SKU/NPI User

     * @param int $customerId
     */
    public function getItemDiscountMessage($customerId)
    {
        $itemdiscountMessage = '';
        $customerSegments = [];
        $appliedRuleIds = [];
        $ruleData = [];
        $cartItems = $this->getCartItems($customerId);
        $customerGroup = $this->getCustomerGroup($customerId);
        $websiteID = $this->storeManager->getStore()->getWebsiteId();
        $customerSegments = $this->customer->getCustomerSegmentIdsForWebsite($customerId, $websiteID);
        $objrules = $this->ruleFactory->create();
        $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
        $appliedruleid = $quote->getAppliedRuleIds();
        if (!empty($appliedruleid)) {
            $appliedRuleIds = explode(',', $appliedruleid);
        }
        $rules[] = $objrules->getCollection()->addFieldToFilter("is_active", ['eq' => "1"]);
        foreach ($rules as $cartRules) {
            $data = $cartRules->getData();
        }
        $applied = false;
        foreach ($data as $ruleData) {
            if (isset($ruleData['discount_type_rule']) && $ruleData['discount_type_rule'] == 1) {
                if (in_array($ruleData['rule_id'], $appliedRuleIds)) {
                    $applied = true;
                }
            }
            if ($applied == false) {
                if (isset($ruleData['discount_type_rule']) && $ruleData['discount_type_rule'] == 2) {
                    if (in_array($ruleData['rule_id'], $appliedRuleIds)) {
                        $applied = true;
                    }
                }
            }
            if ($applied == false) {
                if (isset($ruleData['discount_type_rule']) && $ruleData['discount_type_rule'] == 3) {
                    if (in_array($ruleData['rule_id'], $appliedRuleIds)) {
                        $applied = true;
                    }
                }
            }
        }
        $message = [];
        $itemdiscountMessage = [];
        foreach ($data as $ruleData) {
            $skucond = [];
            if ($applied == false) {
                if (isset($ruleData['discount_type_rule']) && $ruleData['discount_type_rule'] == 4) {
                    $ruleCustomerSegmentID = [];
                    $cartPriceRule = $this->ruleFactory->create()->load($ruleData['rule_id']);
                    if($this->checkCustomerEligibility($customerId,$ruleData['rule_id']) == 1){
                    $customerGroupIds = $cartPriceRule->getCustomerGroupIds();
                    foreach ($customerGroupIds as $customerGroupId) {
                        if ($customerGroup == $customerGroupId) {
                            $dataToEncode = $ruleData['conditions_serialized'];
                            $conditions = $this->serializerInterface->unserialize($dataToEncode);

                            foreach ($conditions['conditions'] as $condition) {
                                if ($condition['type'] == 'Magento\CustomerSegment\Model\Segment\Condition\Segment') {
                                    $ruleCustomerSegmentID = explode(',', $condition['value']);
                                }
                            }
                            foreach ($conditions['conditions'] as $condition) {
                                if ($condition['type'] == 'Magento\SalesRule\Model\Rule\Condition\Product\Found') {
                                    $nextsubConditions = $condition['conditions'];
                                    foreach ($nextsubConditions as $nextsubCondition) {
                                        if ($nextsubCondition['type'] == 'Magento\SalesRule\Model\Rule\Condition\Product') {
                                            $skucond[] = $nextsubCondition['value'];
                                        }
                                    }
                                    try {
                                        if ($this->getIsProductEnable($skucond[0]) == 1) {
                                            foreach ($ruleCustomerSegmentID as $ruleSegmentId) {
                                                if (in_array($ruleSegmentId, $customerSegments) && count($customerSegments) == 1) {
                                                    if (!empty($skucond) && !empty($cartItems)) {
                                                        if (in_array($skucond[0], $cartItems)) {
                                                            $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
                                                            if ($quote->getId()) {
                                                                foreach ($quote->getAllItems() as $item) {
                                                                    if ($item->getSku() == $skucond[0]) {
                                                                        $price = $item->getPrice();
                                                                        $productQty = $item->getQty();
                                                                        $rowTotal = ($price * $productQty);
                                                                    }
                                                                }
                                                            }
                                                            if ($rowTotal >= $skucond[1]) {
                                                                if (!empty($cartItems) && in_array($ruleData['rule_id'], $appliedRuleIds) &&
                                                                $this->cartDetails->getDiscountData($quote) != 0
                                                                ) {
                                                                    $itemdiscountMessage = '{"context":"benefit"}';
                                                                    $message[$skucond[0]] = $itemdiscountMessage;
                                                                }
                                                            } else {
                                                                $itemdiscountMessage = '{"context":"buymorecart","discount":"' . $this->helperData->currency($ruleData['discount_amount'], false, false) . '","sku":"' . $this->getProductNameBySku($skucond[0]) . '","price":"' . $this->helperData->currency($skucond[1], false, false) . '"}';
                                                                $message[$skucond[0]] = $itemdiscountMessage;
                                                            }
                                                        } else {
                                                            $itemdiscountMessage = '{"context":"buymorecart","discount":"' . $this->helperData->currency($ruleData['discount_amount'], false, false) . '","sku":"' . $this->getProductNameBySku($skucond[0]) . '","price":"' . $this->helperData->currency($skucond[1], false, false) . '"}';
                                                            $message[$skucond[0]] = $itemdiscountMessage;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } catch (NoSuchEntityException $e) {
                                        $this->logger->info($e->getMessage());
                                    } catch (\Exception $e) {
                                        $this->logger->info($e->getMessage());
                                    }
                                }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $message;
    }

    /**
     * Get All B2B Item Discount Message for SKU/NPI User

     * @param int $customerId
     */
    public function getItemMessage($customerId)
    {
        $message = $this->getItemDiscountMessage($customerId);
        return $message;
    }

    /**
     * Get All Cart Items

     * @param int $customerId
     */
    public function getCartItems($customerId)
    {
        $arr = [];
        $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
        if ($quote->getId()) {
            foreach ($quote->getAllItems() as $item) {
                $sku = $item->getSku();
                $arr[] = $sku;
            }
        }
        return $arr;
    }

    /**
     * Get RowTotal for matched Product

     * @param int $customerId
     * @param string $sku
     */
    public function getRowTotal($customerId, $sku)
    {
        $rowTotal = '';
        $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
        if ($quote->getId()) {
            foreach ($quote->getAllItems() as $item) {
                if ($item->getSku() == $sku) {
                    $price = $item->getPrice();
                    $productQty = $item->getQty();
                    $rowTotal = ($price * $productQty);
                }
            }
            return $rowTotal;
        }
    }

    /**
     * Get All Applied rule ID of the quote

     * @param int $customerId
     */
    public function getAppliedIds($customerId)
    {
        $quote = $this->cartRepositoryInterface->getActiveForCustomer($customerId);
        $id = $quote->getId();
        $quote = $this->quoteFactory->create()->loadActive($id);
        return $quote->getAppliedRuleIds();
    }

    /**
     * Get Customer Group

     * @param int $customerId
     */
    public function getCustomerGroup($customerId)
    {
        $customerGroup = '';
        $customer = $this->customerFactory->create()->load($customerId);
        if ($customer && $customer->getId()) {
            $customerGroup = $customer->getGroupId();
        }
        return $customerGroup;
    }

    public function getProductNameBySku($sku)
    {
        $productName = '';
        $product = $this->productRepository->get($sku);
        $productName = $product->getName();
        return $productName;
    }

    /**
     * Get Is Migrated

     * @param int $customerId
     */
    public function isMigrated($customerId)
    {
        $customer = $this->customerRepository->getById($customerId);
        if ($customer->getCustomAttribute('is_migrated')) {
            return $customer->getCustomAttribute('is_migrated')->getValue();
        }
    }

    /**
     * Get Product Status

     * @param int $sku
     */
    public function getIsProductEnable($sku)
    {
        try{
        $product = $this->productRepository->get($sku);
        $status = $product->getStatus();
        return $status;
        }
        catch (NoSuchEntityException $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Get checkCustomerEligibility

     * @param int $customerId
     * @param int $ruleId
     */
    public function checkCustomerEligibility($customerId, $ruleId)
    {
        $maxUses = '';
        $rule = $this->ruleFactory->create()->load($ruleId);
        $maxUses = $rule->getUsesPerCustomer();
        $ruleCustomer = $this->ruleCustomerFactory->create();
        $ruleCustomer->loadByCustomerRule($customerId, $ruleId);
        if ($ruleCustomer->getId()) {
            if($ruleCustomer->getTimesUsed() < $maxUses || $maxUses == 0){
                return true;
            }
            else{
                return false;
            }
        }
        if($ruleCustomer->getId() == ''){
            return true;
        }
    }

     /**
     * Get Revert discount when order gets cancelled

     * @param int $customerId
     * @param int $ruleId
     */
    public function setCustomerTimesUsed($customerId, $ruleId)
    {
        try{
        $timesUsed = '';
        $ruleId = explode(',', $ruleId);
        foreach($ruleId as $ruleIdApplied){
        $rule = $this->ruleFactory->create()->load($ruleIdApplied);
        $ruleCustomer = $this->ruleCustomerFactory->create();
        $ruleCustomer->loadByCustomerRule($customerId, $ruleIdApplied);
        if ($ruleCustomer->getId()) {
            $timesUsed = $ruleCustomer->getTimesUsed();
            $ruleCustomer->setTimesUsed($timesUsed - 1);
            $ruleCustomer->save();
            }
            else{
                return false;
            }
        if($ruleCustomer->getId() == ''){
            return true;
        }
    }
    }catch (NoSuchEntityException $e) {
        $this->logger->info($e->getMessage());
        }
    }
}
