<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\BulkOrder\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\GraphQl\Config\Element\Field;
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
use Magento\Store\Model\StoreManagerInterface;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\GetDiscountMessage;
use Bat\BulkOrder\Model\Resolver\CartData;

/**
 * @inheritdoc
 */
class GetBulkOrderReviewDetails extends AbstractModel
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GetDiscountMessage
     */
    protected $getDiscountMessage;

    /**
     * @var CartData
     */
     protected $cartData;

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
     * @param StoreManagerInterface $storeManager
     * @param GetDiscountMessage $getDiscountMessage
     * @param CartData $cartData
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
        StoreManagerInterface $storeManager,
        GetDiscountMessage $getDiscountMessage,
        CartData $cartData
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
        $this->storeManager = $storeManager;
        $this->getDiscountMessage = $getDiscountMessage;
        $this->cartData = $cartData;
    }

    /**
     * @inheritdoc
     */
    public function getBulkOrderDetails($parentId, $reviewPageData)
    {

        $parentOutlet = $parentId;
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

        $storeId = (int) $this->storeManager->getStore()->getStoreId();
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $cartData = [];
        $finalData = [];
        $totalQty = 0;
        $totalCounts = 0;
        $isParent = false;

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
            $discountMessages = [];
            if (count($discountMessage) > 0) {

                foreach ($discountMessage as $key => $value) {
                    $messages = json_decode($value);
                    if ($messages->context == 'benefit') {
                        $discountMessages[] = 'You Received Benefit';
                    } elseif ($messages->context == 'buymorecartitem') {
                        $discountMessages[] = 'Buy more than '.$messages->price. ' and get '.$messages->discount.' KRW discount';
                    } elseif ($messages->context == 'firstorder') {
                        $discountMessages[] = 'Buy all Products from Recommended for you Requisition List and get '.$messages->discount.' KRW discount';
                    } else {
                        $discountMessages[] = 'Buy '.$messages->sku.' more than '.$messages->price. ' and get '.$messages->discount.' KRW discount';
                    }
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
            $cartPricesData = $this->cartData->getCartPrices($cart);
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
            $cartData['subtotal'] = $cartPricesData['subtotal_including_tax'];
            $cartData['bulk_discount_message'] = $discountMessages;
            $cart->collectTotals();

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

        if ($reviewPageData == 'parent') {
            return $outletData;
        } else {
            return $finalData;
        }
    }
}
