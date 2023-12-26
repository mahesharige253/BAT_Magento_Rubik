<?php

namespace Bat\PriceDecimal\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Currency;

class Data extends AbstractHelper
{

    /**
      * @var StoreManagerInterface
      */
    protected StoreManagerInterface $storeConfig;

    /**
     * @var Currency
     */ 
    protected Currency $currency;

    /**
     * @param Currency $currency
     * @param StoreManagerInterface $storeConfig
     */
    public function __construct(
        Currency $currency,
        StoreManagerInterface $storeConfig
    ) {
        $this->currency = $currency;
        $this->storeConfig = $storeConfig;
    }

    /**
     * Get Formated Price
     *
     * @param string $price
     */
    public function getFormatedPrice($price)
    {
        $storeId = $this->storeConfig->getStore()->getId();
        $store =  $this->storeConfig->getStore($storeId);
        $currencyCode = $store->getBaseCurrency()->getCurrencyCode();
        $basePurchaseCurrency = $this->currency->load($currencyCode);
        return $basePurchaseCurrency->format($price,['precision' => 0],false);
    }
}
