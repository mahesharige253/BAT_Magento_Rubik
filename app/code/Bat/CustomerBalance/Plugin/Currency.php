<?php

namespace Bat\CustomerBalance\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Currency as ModelCurrency;
use Magento\Framework\App\ObjectManager;

class Currency
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeConfig;

    /**
     * @var ModelCurrency
     */
    protected ModelCurrency $currency;

    /**
     * @param StoreManagerInterface $storeConfig
     * @param ModelCurrency $currency
     */
    public function __construct(
        StoreManagerInterface $storeConfig,
        ModelCurrency $currency = null
    ) {
        $this->storeConfig = $storeConfig;
        $this->currency = $currency ?: ObjectManager::getInstance()
            ->get(ModelCurrency::class);
    }

    /**
     * After Render
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency $subject
     * @param string $result
     * @param decimal $price
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRender(\Magento\Backend\Block\Widget\Grid\Column\Renderer\Currency $subject, $result, $price)
    {
        $storeId = $this->storeConfig->getStore()->getId();
        $store = $this->storeConfig->getStore($storeId);
        $currencyCode = $store->getBaseCurrency()->getCurrencyCode();

        $basePurchaseCurrency = $this->currency->load($currencyCode);
        return $result = $basePurchaseCurrency
            ->format(
                preg_replace(
                    '/[ ,]+/',
                    '',
                    $result
                ),
                ['precision' => 0],
                false
            );
    }
}
