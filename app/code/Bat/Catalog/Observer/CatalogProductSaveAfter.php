<?php
namespace Bat\Catalog\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @class CatalogProductSaveAfter
 * Check expiry date while product save/update
 */
class CatalogProductSaveAfter implements ObserverInterface
{
    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;
 
    /**
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        TimezoneInterface $timezoneInterface
    ) {
        $this->timezoneInterface = $timezoneInterface;
    }
    /**
     * Check expiry date
     *
     * @param EventObserver $observer
     * @return boolean
     */
    public function execute(EventObserver $observer)
    {
        $product = $observer->getProduct();
        $expiryDate = $product->getExpiryDate();

        $currentDate = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
        
        if ($expiryDate < $currentDate) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Expiry date must be greater than past date"));
        }
        return true;
    }
}
