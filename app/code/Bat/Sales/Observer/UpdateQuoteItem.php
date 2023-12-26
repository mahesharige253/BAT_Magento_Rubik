<?php
namespace Bat\Sales\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * @class UpdateQuoteItem
 * Add custom data to quote item
 */
class UpdateQuoteItem implements ObserverInterface
{
    /**
     * Set custom data in quote item
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $item = $observer->getEvent()->getData('quote_item');
        $product = $item->getProduct();
        $item->setUom($product->getUom());
        $item->setBaseToSecondaryUom($product->getBaseToSecondaryUom());
        $item->setIsPriceTag($product->getPricetagType());
    }
}
