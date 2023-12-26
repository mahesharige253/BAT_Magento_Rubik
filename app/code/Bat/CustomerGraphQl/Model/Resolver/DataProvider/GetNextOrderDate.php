<?php

namespace Bat\CustomerGraphQl\Model\Resolver\DataProvider;

use Bat\CustomerGraphQl\Model\OrderNextDate;
use Bat\CustomerGraphQl\Model\OrderRegularFrequencyNextDate;

class GetNextOrderDate
{
    /**
     * @var OrderNextDate
     */
    private $orderNextDate;

    /**
     * @var OrderRegularFrequencyNextDate
     */
    private $orderRegularFrequencyNextDate;

    /**
     * Construct method
     *
     * @param OrderNextDate $orderNextDate
     * @param OrderRegularFrequencyNextDate $orderRegularFrequencyNextDate
     */
    public function __construct(
        OrderNextDate $orderNextDate,
        OrderRegularFrequencyNextDate $orderRegularFrequencyNextDate
    ) {
        $this->orderNextDate = $orderNextDate;
        $this->orderRegularFrequencyNextDate = $orderRegularFrequencyNextDate;
    }

    /**
     * Get Next Order Nearest Date as per Regular or Joker Order Frequency

     * @param string $customer
     */
    public function getNextOrderDate($customer)
    {
        return $this->orderNextDate->getClosestDate($customer);
    }

    /**
     * Get Next Order Date as per Regular Frequency

     * @param string $customer
     */
    public function getNextOrderRegularFrequencyDate($customer)
    {
        return $this->orderRegularFrequencyNextDate->getClosestDate($customer);
    }
}
