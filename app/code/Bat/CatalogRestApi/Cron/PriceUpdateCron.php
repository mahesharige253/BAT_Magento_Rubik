<?php
namespace Bat\CatalogRestApi\Cron;

use Bat\CatalogRestApi\Model\PricesMasterUpdate;

class PriceUpdateCron
{
    /**
     * @var PricesMasterUpdate
     */
    protected $priceMaster;

    /**
     * PriceMaster Cron Construct
     *
     * @param PricesMasterUpdate $priceMaster
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */

    public function __construct(PricesMasterUpdate $priceMaster)
    {
        $this->priceMaster = $priceMaster;
    }

    /**
     * Generate outlet csv
     *
     * @return void
     */
    public function execute()
    {
        $this->priceMaster->addLog('Price Master Cron Started.');
        if ($this->priceMaster->getStatus()) {
            $this->priceMaster->updateProductPrice();
        }
        $this->priceMaster->addLog('Price Master Cron Finished.');
    }
}
