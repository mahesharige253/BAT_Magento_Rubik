<?php
namespace Bat\BestSellers\Cron;

use Bat\BestSellers\Model\PrepareBestSellers;
use Magento\Framework\App\Config\ScopeConfigInterface;

class BestSellersCron
{
    /**
     * @var PrepareBestSellers
     */
    protected $prepareBestSellers;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     * @param PrepareBestSellers $prepareBestSellers
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        PrepareBestSellers $prepareBestSellers,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->prepareBestSellers = $prepareBestSellers;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Generate bestseller custom table to use it all the places
     *
     * @return void
     */
    public function execute()
    {
        if ($this->isCronEnabled()) {
            $this->prepareBestSellers->generateBestSellers();
        }
    }

    /**
     * Function to check cron enabled
     *
     * @return mixed
     */
    private function isCronEnabled()
    {
        return $this->scopeConfig->getValue("best_sellers/bestseller_cron/is_active");
    }
}
