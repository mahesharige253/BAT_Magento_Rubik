<?php
namespace Bat\QuoteGraphQl\Model;

use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\BestSellers\Model\BestSellersUpdate;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Bat\Sales\Block\ProductDetail as ProductDefaultAttribute;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Bat\Customer\Helper\Data as CustomerHelper;
use Magento\Directory\Model\Currency;
use Magento\Framework\Currency\Data\Currency as CurrencyData;

class AfterPlaceOrder extends AbstractModel
{
    /**
      * @var KakaoSms
      */
    private KakaoSms $kakaoSms;

    /**
      * @var BestSellersUpdate
      */
    private BestSellersUpdate $bestSellerUpdateFactory;

    /**
      * @var OrderItemRepositoryInterface
      */
    private OrderItemRepositoryInterface $orderItemRepository;

    /**
      * @var ProductDefaultAttribute
      */
    private ProductDefaultAttribute $productDefaultAttribute;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var CustomerHelper
     */
    private CustomerHelper $customerHelper;

    /**
     * @var Currency
     */
    protected Currency $currency;

    /**
     * @param KakaoSms $kakaoSms
     * @param BestSellersUpdate $bestSellerUpdateFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductDefaultAttribute $productDefaultAttribute
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerHelper $customerHelper
     * @param Currency $currency
     */
    public function __construct(
        KakaoSms $kakaoSms,
        BestSellersUpdate $bestSellerUpdateFactory,
        OrderItemRepositoryInterface $orderItemRepository,
        ProductDefaultAttribute $productDefaultAttribute,
        ProductRepositoryInterface $productRepository,
        CustomerHelper $customerHelper,
        Currency $currency
    ) {
        $this->kakaoSms = $kakaoSms;
        $this->bestSellerUpdateFactory = $bestSellerUpdateFactory;
        $this->orderItemRepository = $orderItemRepository;
        $this->productDefaultAttribute = $productDefaultAttribute;
        $this->productRepository = $productRepository;
        $this->customerHelper = $customerHelper;
        $this->currency = $currency;
    }

    /**
     * Add/Update quantity in bat_bestseller table as per Sigungu code
     *
     * @param String $customer
     * @param String $order
     * @return null
     */
    public function afterPlace($customer, $order)
    {
        if ($customer->getCustomAttribute('mobilenumber')) {
            $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
            $vbaBankInfo = '';

            if($customer->getCustomAttribute('virtual_bank') && $customer->getCustomAttribute('virtual_account')) {
                $vbaBankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
                $vbaBankNumber = $customer->getCustomAttribute('virtual_account')->getValue();
                $vbaBankName = $this->customerHelper->getAttributeLabelByValue('virtual_bank', $vbaBankCode);
                $vbaBankInfo = $vbaBankName.', '.$vbaBankNumber;
            }
            $orderSubTotalInclTax = $order->getSubtotalInclTax();
            $orderIncrementId = $order->getIncrementId();

            $firstItemName = '';
            $orderQty = 0;
            $sigunguCode = '';
            if ($customer->getCustomAttribute('sigungu_code')) {
                $sigunguCode = $customer->getCustomAttribute('sigungu_code')->getValue();
            }
            $i= 0;
            foreach ($order->getAllItems() as $item) {
                if ($item->getIsPriceTag() == 0) {
                    if ($i == 0) {
                        $firstItemName = $item->getName();
                    }
                    $orderQty += $item->getQtyOrdered();
                    $this->updateQtyInBestSeller($item->getSku(), $item->getProductId(), $item->getQtyOrdered(), $sigunguCode);

                    /*Save data in sales_order_item table*/
                    $orderItem = $this->orderItemRepository->get($item->getItemId());
                    $defaultAttribute = $this->productDefaultAttribute->getDefaultAttributeValue($item->getSku());
                    $orderItem->setDefaultAttribute($defaultAttribute);

                    $product = $this->productRepository->get($item->getSku());
                    $shortProdNm = $product->getShortProdNm();
                    $orderItem->setShortProdNm($shortProdNm);
                    $orderItem->setUom($product->getUom());
                    $orderItem->setBaseToSecondaryUom($product->getBaseToSecondaryUom());
                    $productImage = $this->getProductImage($item->getSku());
                    $orderItem->setProductImage($productImage);
                    $orderItem->save();
                    $i++;
                }
            }
            if ($i > 1) {
                $firstItemName = $firstItemName.' 외 '.(--$i).' 개';
            }
            $orderCreatedDate = date('Y-m-d', strtotime($order->getCreatedAt()));
            /* Kakao SMS for order placed */
            $params = [
                'salesorder_number' => $orderIncrementId,
                'salesorder_date' => $orderCreatedDate,
                '1stsalesorderproduct_others' => $firstItemName,
                'totalsalesorder_qty' => $orderQty,
                'totalsalesorder_amount' => $this->currency->format($orderSubTotalInclTax, ['display'=> CurrencyData::NO_SYMBOL, 'precision' => 0], false),
                'vbabank_vbanumber' => $vbaBankInfo
            ];
            $this->kakaoSms->sendSms($mobileNumber, $params, 'SalesOrder_001');
        }
    }

    /**
     * Add/Update quantity in bat_bestseller table as per Sigungu code
     *
     * @param String $sku
     * @param String $productId
     * @param String $qty
     * @param String $sigunguCode
     * @return null
     */
    public function updateQtyInBestSeller($sku, $productId, $qty, $sigunguCode)
    {
        if($sigunguCode !='') {
            $this->bestSellerUpdateFactory->addUpdateQtyBestSeller($sku, $productId, $sigunguCode, $qty);
        }
    }

    /**
     * Get Product Image
     *
     * @param String $sku
     * @return String
     */
    public function getProductImage($sku)
    {
        $imageUrl = '';
        $product = $this->productRepository->get($sku);
        try {
            $customAttributeValue = $product->getData('images');
            if ($customAttributeValue != '') {
                $productImageDecode = json_decode($customAttributeValue);
            }
            if (!empty($productImageDecode) && is_array($productImageDecode)) {
                $data = get_object_vars($productImageDecode[0]);
                $imageUrl = base64_encode($data['fileURL']);
            }
        } catch (Exception $e) {
            $imageUrl = '';
        }
        return $imageUrl;
    }
}
