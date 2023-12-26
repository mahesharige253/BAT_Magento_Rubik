<?php

namespace Bat\PriceDecimal\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\ObjectManager;

class PurchasedPrice
{
    /**
     * @var Currency
     */
    protected Currency $currency;

    /**
     * @param Currency $currency
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency ?: ObjectManager::getInstance()
            ->get(Currency::class);
    }

    /**
     * After Prepare Data Source
     *
     * @param \Magento\Sales\Ui\Component\Listing\Column\PurchasedPrice $subject
     * @param array $result
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepareDataSource(\Magento\Sales\Ui\Component\Listing\Column\PurchasedPrice $subject, $result)
    {
        if (isset($result['data']['items'])) {
            foreach ($result['data']['items'] as & $item) {
                $currencyCode = isset($item['order_currency_code']) ? $item['order_currency_code'] : null;
                $purchaseCurrency = $this->currency->load($currencyCode);
                $item['grand_total'] = $purchaseCurrency
                    ->format(preg_replace('/[ ,]+/', '', $item['grand_total']), ['precision' => 0], false);
            }
        }

        return $result;
    }
}
