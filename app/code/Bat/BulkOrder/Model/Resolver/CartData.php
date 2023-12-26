<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Bat\BulkOrder\Model\ValidateCSVdata;
use Magento\Company\Api\CompanyManagementInterface;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Bat\BulkOrder\Model\CreateBulkCart;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Bat\OrderProducts\Helper\Data as OrderProductsHelper;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;
use Magento\Quote\Model\Quote\Address\Total;

/**
 * @inheritdoc
 */
class CartData implements ResolverInterface
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var ValidateCSVdata
     */
    private $validateCsvData;

    /**
     * @var CompanyManagementInterface
     */
    private $companyRepository;

    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * @var CollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var CreateBulkCart
     */
    protected $bulkOrderData;

    /**
     * @var OrderProductsHelper
     */
    protected $orderProductsHelper;

    /**
     * @var GetCustomer
     */
    protected $getCustomer;

    /**
     * @var GetDiscountMessage
     */
    protected $getDiscountMessage;

     /**
      * @var TotalsCollector
      */
    private $totalsCollector;

    /**
     * @param GetCartForUser $getCartForUser
     * @param ValidateCSVdata $validateCsvData
     * @param CompanyManagementInterface $companyRepository
     * @param CartDetails $cartDetails
     * @param CollectionFactory $quoteCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param Data $helper
     * @param CreateBulkCart $bulkCartdata
     * @param OrderProductsHelper $orderProductsHelper
     * @param GetCustomer $getCustomer
     * @param GetDiscountMessage $getDiscountMessage
     * @param TotalsCollector $totalsCollector
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        ValidateCSVdata $validateCsvData,
        CompanyManagementInterface $companyRepository,
        CartDetails $cartDetails,
        CollectionFactory $quoteCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        Data $helper,
        CreateBulkCart $bulkCartdata,
        OrderProductsHelper $orderProductsHelper,
        GetCustomer $getCustomer,
        GetDiscountMessage $getDiscountMessage,
        TotalsCollector $totalsCollector
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->validateCsvData = $validateCsvData;
        $this->companyRepository = $companyRepository;
        $this->cartDetails = $cartDetails;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->helper = $helper;
        $this->bulkOrderData = $bulkCartdata;
        $this->orderProductsHelper = $orderProductsHelper;
        $this->getCustomer = $getCustomer;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->totalsCollector = $totalsCollector;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input'])) {
            throw new GraphQlInputException(__('Required parameter "parent_outlet" is missing'));
        }
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $websiteId = $context->getExtensionAttributes()->getStore()->getWebsiteId();
        $customer = $this->customerRepository->getById($customerId);
        $customerCustomAttributes = $customer->getCustomAttributes();
        if (isset($customerCustomAttributes['is_parent'])) {
            $isCreditCustomer = $customerCustomAttributes['is_parent'];
            if ($isCreditCustomer->getAttributecode() == "is_parent"
                && empty($isCreditCustomer->getValue())
            ) {
                throw new GraphQlNoSuchEntityException(
                    __(
                        'This customer is not parent. Please try with parent customer'
                    )
                );
            }
        }
        $parentOutlet = $args['input']['parent_outlet'];
        $parentCustomerId = $this->cartDetails->getCustomerIdsByCustomAttribute($parentOutlet);
        $parentId = $parentCustomerId[0];
        $isParentCreditCustomer = $this->helper->isCreditCustomer($parentId);
        $customer = $this->customerRepository->getById($parentId);
        $company = $this->companyRepository->getByCustomerId($parentId);
        $parentCompanyname = $company->getCompanyName();
        $parentaccountno = $customer->getCustomAttribute('virtual_account')->getValue();
        $parentbankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
        $parentbankName = $this->cartDetails->getAttributeLabelByValue('virtual_bank', 'customer', $parentbankCode);
        $quote = $this->quoteCollectionFactory->create()->
            addFieldToFilter('parent_outlet_id', $parentOutlet)->addFieldToFilter('is_active', 1);
        $totalStores = count($quote->getAllIds());
        $dataDetails = $quote->getData();
        $quoteData = [];
        $inputs = [];
        $data = [];
        foreach ($dataDetails as $dataDetail) {
            $quoteData['outlet_id'] = $dataDetail['outlet_id'];
            $quoteId = $dataDetail['entity_id'];
            $entityId = (int) $quoteId;
            $maskedCartId = $this->cartDetails->getQuoteMaskedIdByEntityId($entityId);
            $quoteData['maskedCartId'] = $maskedCartId;
            $inputs[] = $quoteData;
        }
        $bulkSubtotal = 0;
        $bulkTotal = 0;
        $bulkRemainingAr = 0;
        $bulkOverpayment = 0;
        $bulkMinPayment = 0;
        $totalDiscount = 0;
        $bulkNetAmount = 0;
        $bulkVatAmount = 0;

        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        $websiteId = $context->getExtensionAttributes()->getStore()->getWebsiteId();
        $cartData = [];
        $finalData = [];
        $totalQty = 0;
        $totalCounts = 0;
        $isParent = false;
        $deadline = 0;
        $paymentDeadline = '';
        $firstPaymentDeadline = '';

        foreach ($inputs as $input) {
            $maskedCartId = $input['maskedCartId'];
            $outletIds = $input['outlet_id'];
            $userId = $this->cartDetails->getCustomerIdsByCustomAttribute($outletIds);
            $currentUserId = (int) $userId[0];
            $customer = $this->customerRepository->getById($currentUserId);
            $firstname = $customer->getFirstname();
            $accountno = $customer->getCustomAttribute('virtual_account')->getValue();
            $bankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
            $bankName = $this->cartDetails->getAttributeLabelByValue('virtual_bank', 'customer', $bankCode);
            $activeStatus = $this->cartDetails->getCartActive($maskedCartId);
            $isParent = $this->validateCsvData->isParentOutlet($outletIds);
            $company = $this->companyRepository->getByCustomerId($currentUserId);
            $companyname = $company->getCompanyName();
            $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);
            $cartItems = $this->cartDetails->getCartItemsCount($cart);
            $cartItemsQty = $this->cartDetails->getCartItemsQty($cart);

            $isCreditCustomer = $this->helper->isCreditCustomer($currentUserId);
            $isCustomerFirstOrder = $this->helper->getIsCustomerFirstOrder($currentUserId);
            $discountMessage = [];
            $cart->collectTotals();
            $discountMessage = $this->getDiscountMessage->getDiscountMessage($currentUserId);
            $itemdiscountMessage[0] = $this->getDiscountMessage->getItemMessage($currentUserId);
            if (!empty($itemdiscountMessage[0])) {
                foreach ($itemdiscountMessage[0] as $itemDiscount) {
                    if ($itemDiscount == '{"context":"benefit"}') {
                        $i = array_search('{"context":"benefit"}', $itemdiscountMessage[0]);
                        unset($itemdiscountMessage[0][$i]);
                    }
                }
                if (!empty($itemdiscountMessage[0])) {
                    $discountMessage = $itemdiscountMessage[0];
                } else {
                    $discountMessage = ['{"context":"benefit"}'];
                }
            }
            $cartData['companyName'] = $companyname;
            $cartData['cartData'] = $cart;
            $cartData['cartItems'] = $cartItems;
            $cartData['cartItemsQty'] = $cartItemsQty;
            $cartData['maskedCartId'] = $maskedCartId;
            $cartData['outlet_id'] = $outletIds;
            $cartData['activeStatus'] = $activeStatus;
            $cartData['isParent'] = $isParent;
            $cartData['accountHolderName'] = $companyname;
            $cartData['accountNumber'] = $accountno;
            $cartData['bankCode'] = $bankCode;
            $cartData['bankName'] = $bankName;
            $cart->collectTotals();
            $cartPricesData = $this->getCartPrices($cart);
            $customerCartData = $this->helper->getCustomerCartSummary($cart->getCustomerId(), $websiteId, $cartPricesData['grand_total']);
            $cartData['overpayment'] = $customerCartData['overpayment'];
            $cartData['order_grand_total'] = $customerCartData['grand_total'];
            $cartData['net'] = $cart->getSubtotal();
            $cartData['vat'] = $cart->getGrandTotal() - $cart->getSubtotal();
            $remainingAr = 0;
            if (isset($customerCartData['remaining_ar'])) {
                $remainingAr = $customerCartData['remaining_ar'];
            }
            $cartData['remaining_ar'] = $remainingAr;
            $minimumPayment = 0;
            if (isset($customerCartData['minimum_payment'])) {
                $minimumPayment = $customerCartData['minimum_payment'];
            }
            $cartData['minimum_payment'] = $minimumPayment;
            $cartData['is_credit_customer'] = $isCreditCustomer;
            $cartData['is_first_order'] = $isCustomerFirstOrder;
            $cartData['discount'] = $cartPricesData['discount'];
            $cartData['bulk_discount_message'] = $discountMessage;

            $finalData[] = $cartData;
            $bulkSubtotal += $cartPricesData['subtotal_including_tax'];
            $bulkTotal += $customerCartData['grand_total'];
            $bulkRemainingAr += $remainingAr;
            $bulkOverpayment += $customerCartData['overpayment'];
            $bulkNetAmount += $cartData['net'];
            $bulkVatAmount += $cartData['vat'];
            $bulkMinPayment += $minimumPayment;
            $totalQty += $this->cartDetails->getCartItemsQty($cart);
            $totalCounts += $this->cartDetails->getCartItemsCount($cart);
            $totalDiscount += $cartPricesData['discount'];
        }

        $outletData = [
            'total_stores' => $totalStores,
            'total_qty' => $totalQty,
            'total_count' => $totalCounts,
            'subtotal' => $bulkSubtotal,
            'total_discount' => $totalDiscount,
            'overpayment' => $bulkOverpayment,
            'bulk_net_amount' => $bulkNetAmount,
            'bulk_vat_amount' => $bulkVatAmount,
            'remaining_ar' => $bulkRemainingAr,
            'total' => $bulkTotal,
            'minimum_payment' => $bulkMinPayment,
            'is_credit_customer' => $isParentCreditCustomer,
            'vbaBulkDetails' => [
                'account_holder_name' => $parentCompanyname,
                'account_number' => $parentaccountno,
                'bankDetails' => [
                    'bank_code' => $parentbankCode,
                    'bank_name' => $parentbankName
                ],
            ],
            'payment_deadline_date' => $this->orderProductsHelper->getPaymentDeadline(),
            'first_payment_deadline_date' => $this->orderProductsHelper->getFirstPaymentDeadline()
        ];
        foreach ($finalData as $value) {
            $arr = [
                'cartItemsCount' => $value['cartItems'],
                'cartItemsQty' => $value['cartItemsQty'],
                'is_active' => $value['activeStatus'],
                'masked_cart_id' => $value['maskedCartId'],
                'outlet_id' => $value['outlet_id'],
                'is_parent' => $value['isParent'],
                'outletName' => $value['companyName'],
                'overpayment' => $value['overpayment'],
                'order_grand_total' => $value['order_grand_total'],
                'net' => $value['net'],
                'vat' => $value['vat'],
                'remaining_ar' => $value['remaining_ar'],
                'minimum_payment' => $value['minimum_payment'],
                'bulk_discount_message' => $value['bulk_discount_message'],
                'is_credit_customer' => $value['is_credit_customer'],
                'is_first_order' => $value['is_first_order'],
                'vbaBulkDetails' => [
                    'account_holder_name' => $value['accountHolderName'],
                    'account_number' => $value['accountNumber'],
                    'bankDetails' => [
                        'bank_code' => $value['bankCode'],
                        'bank_name' => $value['bankName']
                    ],
                ],
                'cartData' => [
                    'model' => $value['cartData']
                ],
                'discount' => $value['discount'],
            ];
            $data[] = $arr;
        }
        return ['cartDetails' => $data, 'outletDetails' => $outletData];
    }

    /**
     * Get Cart Prices
     *
     * @param object $cart
     */
    public function getCartPrices($cart)
    {
        $cart->setCartFixedRules([]);
        $cartTotals = $this->totalsCollector->collectQuoteTotals($cart);
        $currency = $cart->getQuoteCurrencyCode();
        return [
            'grand_total' => $cartTotals->getGrandTotal(),
            'subtotal_including_tax' => $cartTotals->getSubtotalInclTax(),
            'discount' => $this->getDiscount($cartTotals, $currency)['amount']['value'] * -1
        ];
    }

    /**
     * Returns information about an applied discount
     *
     * @param Total $total
     * @param string $currency
     * @return array|null
     */
    private function getDiscount(Total $total, string $currency)
    {
        return [
            'label' => $total->getDiscountDescription() !== null ? explode(', ', $total->getDiscountDescription()) : [],
            'amount' => ['value' => $total->getDiscountAmount(), 'currency' => $currency]
        ];
    }
}
