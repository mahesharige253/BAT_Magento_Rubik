<?php

namespace Bat\PriceDecimal\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\ObjectManager;

class Price
{
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var Currency
     */
    protected Currency $currency;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Currency $currency
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Currency $currency = null
    ) {
        $this->storeManager = $storeManager;
        $this->currency = $currency ?: ObjectManager::getInstance()
            ->get(Currency::class);
    }

    /**
     * After Prepare Data Source
     *
     * @param \Magento\Sales\Ui\Component\Listing\Column\Price $subject
     * @param array $result
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepareDataSource(\Magento\Sales\Ui\Component\Listing\Column\Price $subject, $result)
    {
        if (isset($result['data']['items'])) {
            foreach ($result['data']['items'] as & $item) {
                $currencyCode = $item['base_currency_code'] ?? null;

                if (!$currencyCode) {
                    $itemStoreId = $item['store_id'] ?? '';
                    $storeId = $itemStoreId && is_numeric($itemStoreId) ? $itemStoreId :
                      $this->getStoreId();
                    $store = $this->storeManager->getStore($storeId);
                    $currencyCode = $store->getBaseCurrency()->getCurrencyCode();
                }
                $basePurchaseCurrency = $this->currency->load($currencyCode);
                $item['base_grand_total'] = $basePurchaseCurrency
                                            ->format(
                                                preg_replace(
                                                    '/[ ,]+/',
                                                    '',
                                                    $item['base_grand_total']
                                                ),
                                                ['precision' => 0],
                                                false
                                            );
            }
        }
        return $result;
    }

    /**
     * Get Store Id
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
