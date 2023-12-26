<?php

namespace Bat\BulkOrder\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\DataObject\Factory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\ShippingAddressManagementInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Bat\BulkOrder\Block\Adminhtml\ChildOutlet;
use Magento\Framework\App\ResourceConnection;

class CreateBulkCart extends AbstractModel
{

    /**
     * @var GuestCartManagementInterface
     */
    private $guestCart;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Factory
     */
    private $dataObjectFactory;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var CollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuoteId;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Repository for customer address to perform crud operations
     *
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var AddressFactory
     */
    protected $quoteAddressFactory;

    /**
     * @var ShippingAddressManagementInterface
     */
    private $shippingAddressManagement;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ChildOutlet
     */
    protected $childoutlet;

    /**
     * @var ResourceConnection 
     */
    protected $resourceConnection;

    /**
     * Construct method
     *
     * @param GuestCartManagementInterface $guestCart
     * @param CartRepositoryInterface $cartRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param FormKey $formKey
     * @param ProductRepository $productRepository
     * @param Factory $dataObjectFactory
     * @param QuoteManagement $quoteManagement
     * @param CollectionFactory $customerCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quoteFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressFactory $quoteAddressFactory
     * @param ShippingAddressManagementInterface $shippingAddressManagement
     * @param BalanceFactory $balanceFactory
     * @param Data $helper
     * @param ChildOutlet $childoutlet
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        GuestCartManagementInterface $guestCart,
        CartRepositoryInterface $cartRepository,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        FormKey $formKey,
        ProductRepository $productRepository,
        Factory $dataObjectFactory,
        QuoteManagement $quoteManagement,
        CollectionFactory $customerCollectionFactory,
        StoreManagerInterface $storeManager,
        QuoteFactory $quoteFactory,
        CustomerRepositoryInterface $customerRepository,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        AddressRepositoryInterface $addressRepository,
        AddressFactory $quoteAddressFactory,
        ShippingAddressManagementInterface $shippingAddressManagement,
        BalanceFactory $balanceFactory,
        Data $helper,
        ChildOutlet $childoutlet,
        ResourceConnection $resourceConnection
    ) {
        $this->guestCart = $guestCart;
        $this->cartRepository = $cartRepository;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->formKey = $formKey;
        $this->productRepository = $productRepository;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteManagement = $quoteManagement;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->customerRepository = $customerRepository;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->addressRepository = $addressRepository;
        $this->_quoteAddressFactory = $quoteAddressFactory;
        $this->shippingAddressManagement = $shippingAddressManagement;
        $this->balanceFactory = $balanceFactory;
        $this->helper = $helper;
        $this->childoutlet = $childoutlet;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Create customer quote.
     *
     * @param array $orderItems
     * @return array
     */
    public function createCart($orderItems)
    {
        $storeId = $this->storeManager->getStore()->getStoreId();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $parentOutletId = $orderItems[0]['parent_outlet_id'];
        $this->clearParentQuote($parentOutletId);
        $cartData = [];
        foreach ($orderItems as $item) {
            $customerData = $this->getCustomer('outlet_id', $item['outlet_id']);
            $customer = $customerData->getFirstItem();
            $quoteData = $this->getCustomerQuote($customer->getId(), $storeId);
            $customerQuote = $this->quoteManagement->createEmptyCartForCustomer($customer->getId());
            $this->createUpdateRecord($customerQuote, $item['outlet_id'], $item['parent_outlet_id']);
            $this->addProductToCart(
                $customerQuote,
                $item['items'],
                (int) $item['outlet_id'],
                (int) $item['parent_outlet_id']
            );
            $this->setShippingAddress($customerQuote, $customer, $websiteId);
            $cartData[$item['outlet_id']] = $this->createMaskId($customerQuote);
        }
        return $cartData;
    }

    /**
     * Creating empty cart.
     *
     * @return String
     */
    public function createEmptyCart()
    {
        return $this->guestCart->createEmptyCart();
    }

    /**
     * Adding products to cart.
     *
     * @param string $cartId
     * @param array $productItems
     * @param int $outletId
     * @param int $parent_outletId
     * @return array
     */
    public function addProductToCart($cartId, $productItems, $outletId, $parent_outletId)
    {
        $this->addLog("===============Start===================");
        $this->addLog("addProductToCart start : $outletId");
        $this->addLog("outlet_id : $outletId");
        $this->addLog("parent outlet id : $parent_outletId");
        $this->addLog("===============END=====================");
        $cart = $this->cartRepository->get($cartId);
        $cart->setOutletId((int) $outletId);
        $cart->setParentOutletId((int) $parent_outletId);
        $this->addLog("addProductToCart after set parent outlet id : ".$cart->getParentOutletId());
        $this->addLog("addProductToCart after set outlet id : ".$cart->getOutletId());
        $pricetagProducts = [];
        $customerData = $this->getCustomer('outlet_id', $outletId);

        $customer = $customerData->getFirstItem();
        $firstOrder = $this->helper->getIsCustomerFirstOrder($customer->getEntityId());
        if ($firstOrder) {
            $priceTagItems = $this->childoutlet->getFirstOrderPriceTag();
            if (!empty($priceTagItems)) {
                foreach($priceTagItems as $item){
                    $productItems[] = ['quantity' => 1, 'sku' => $item];
                }
            }
        }

        foreach ($productItems as $product) {
            $productData = $this->productRepository->get($product['sku']);
            $productQuantity = $product['quantity'];
            $priceTag = $productData->getPricetagType();
            if ($priceTag) {
                $pricetagProducts[] = $product['sku'];
                $productQuantity = 1;
            }
            $params = [
                'form_key' => $this->formKey->getFormKey(),
                'product' => $productData->getId(),
                'qty' => $productQuantity
            ];
            $cart->addProduct(
                $productData,
                $this->dataObjectFactory->create($params)
            );
        }
        $this->cartRepository->save($cart);
        if($cart->getParentOutletId() == '' || $cart->getOutletId() == ''){
           $this->addLog("=========Start set outletId=============="); 
           $this->addLog("addProductToCart not set parent child outlet id: ");
           $cartData = $this->cartRepository->get($cartId);
           $cartData->setOutletId((int) $outletId);
           $cartData->setParentOutletId((int) $parent_outletId);
           $this->addLog("addProductToCart parent outlet id : ".$cartData->getParentOutletId());
           $this->addLog("addProductToCart outlet id : ".$cartData->getOutletId());
           $this->addLog("==========END set outletId===============");
           $this->cartRepository->save($cartData);
        }
        if (count($pricetagProducts) > 0) {
            $this->setPriceTag($cartId, $pricetagProducts);
        }
    }

    private function createUpdateRecord($quoteId, $outletId, $parentOutletId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('quote');
        $data = ["outlet_id" => $outletId, 'parent_outlet_id' => $parentOutletId];
        $where = ['entity_id = ?' => (int)$quoteId];
        $connection->update($tableName, $data, $where);
    }

    /**
     * Getting customer collection.
     *
     * @param string $field
     * @param string $value
     * @return array
     */
    public function getCustomer($field, $value)
    {
        return $this->customerCollectionFactory->create()
            ->addAttributeToFilter($field, $value);
    }

    /**
     * Check customer active quote and make inactive.
     *
     * @param string $customerId
     * @param string $storeId
     * @return Int|String
     */
    public function getCustomerQuote($customerId, $storeId)
    {
        try {
            $quoteData = $this->quoteManagement->getCartForCustomer($customerId);
            $quoteData->setIsActive(0);
            $quoteData->save();
            return $quoteData->Id();

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check customer active quote and make inactive.
     *
     * @param Int $quoteId
     * @return String
     */
    public function createMaskId($quoteId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $quoteIdMask->setQuoteId($quoteId)->save();
        return $quoteIdMask->getMaskedId();
    }

    /**
     * Set Price tag for the quote.
     *
     * @param Int $quoteId
     * @param array $priceTagItems
     */
    public function setPriceTag($quoteId, $priceTagItems)
    {
        $cart = $this->cartRepository->get($quoteId);
        $cartItem = $cart->getAllItems();
        foreach ($cartItem as $item) {
            if (in_array($item['sku'], $priceTagItems)) {
                $cartItem = $cart->getItemById($item['item_id']);
                $cartItem->setIsPriceTag(1);
                $cartItem->save();
            }
        }
    }

    /**
     * Set Shipping address for the quote.
     *
     * @param Int $quoteId
     * @param array $customer
     * @param int $websiteId
     * @return String
     */
    public function setShippingAddress($quoteId, $customer, $websiteId)
    {

        $quote = $this->cartRepository->get($quoteId);
        $defaultShippingAddress = $this->addressRepository->getById($customer->getDefaultShipping());

        $quote->getShippingAddress()->setFirstname($defaultShippingAddress->getFirstname());
        $quote->getShippingAddress()->setLastname($defaultShippingAddress->getLastname());
        $quote->getShippingAddress()->setStreet($defaultShippingAddress->getStreet());
        $quote->getShippingAddress()->setCity($defaultShippingAddress->getCity());
        $quote->getShippingAddress()->setTelephone($defaultShippingAddress->getTelephone());
        $quote->getShippingAddress()->setPostcode($defaultShippingAddress->getPostcode());
        $quote->getShippingAddress()->setCountryId($defaultShippingAddress->getCountryId());

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate');
        $shippingAddress->save();
        $quote->setPaymentMethod('banktransfer'); //payment method
        $quote->setInventoryProcessed(false); //not effetc inventory
        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'banktransfer']);
        $quote->collectTotals();
        $this->cartRepository->save($quote);
    }

    /**
     * Clear Parent Quote.
     *
     * @param Int $parentOutletId
     * @return String
     */
    public function clearParentQuote($parentOutletId)
    {
        $storeId = $this->storeManager->getStore()->getStoreId();
        $customerData = $this->getCustomer('outlet_id', $parentOutletId);
        $customer = $customerData->getFirstItem();
        $quoteData = $this->getCustomerQuote($customer->getId(), $storeId);
        $dataCollection = $this->quoteFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('is_active', 1);
        foreach ($dataCollection as $data) {
            $quoteData = $this->getCustomerQuote($data->getCustomerId(), $storeId);
        }
    }

    /**
     * Add Log
     *
     * @param string $message
     * @throws Zend_Log_Exception
     */
    public function addLog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/create_bulk_quote.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
