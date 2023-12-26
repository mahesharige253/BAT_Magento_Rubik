<?php
namespace Bat\Customer\Model;

use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteRepository;


class SubTotal extends AbstractTotal
{
    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

     /**
     * @param CartDetails $cartDetails
     * @param LoggerInterface $logger
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        CartDetails $cartDetails,
        LoggerInterface $logger,
        QuoteRepository $quoteRepository
    ) {
        $this->cartDetails = $cartDetails;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Collect  Quote data.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $subtotal = $total->getSubtotal();
        $cartDiscount = $total->getSubtotal() - $total->getSubtotalWithDiscount();
        if ($subtotal > 0) {
            if ($cartDiscount == 0) {
                $newSubtotal = round($subtotal / 1.1);
                $taxValue = $subtotal - $newSubtotal;
                $total->setSubtotal($newSubtotal);
                $total->setBaseSubtotal($newSubtotal);
                $total->setTaxAmount($taxValue);
                $total->setBaseTaxAmount($taxValue);
                $total->setTotalAmount('tax',$taxValue);
            } else {
                $discoutedSubtotal = $subtotal - $cartDiscount;
                $newSubtotal = round($discoutedSubtotal / 1.1);
                $taxValue = $discoutedSubtotal - $newSubtotal;
                $total->setSubtotal($newSubtotal);
                $total->setBaseSubtotal($newSubtotal);
                $total->setTaxAmount($taxValue);
                $total->setBaseTaxAmount($taxValue);
                $total->setTotalAmount('tax',$taxValue);
            }
        }
        return $this;
    }
}
