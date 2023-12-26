<?php
namespace Bat\JokerOrder\Helper\Customer;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Get e-call frequency
     *
     * @param object $customer
     * @param datetime $currentDate
     * @return boolean
     */
    public function getEcall($customer, $currentDate)
    {
        if (!empty($customer->getCustomAttribute('joker_order_ecall_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_ecall_end_date'))) {
            $jokerOrderEcallStartDate = $customer->getCustomAttribute('joker_order_ecall_start_date')->getValue();
            $jokerOrderEcallEndDate = $customer->getCustomAttribute('joker_order_ecall_end_date')->getValue();
            $jokerOrderEcallEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderEcallEndDate));
            if (($currentDate >= $jokerOrderEcallStartDate) && ($currentDate <= $jokerOrderEcallEndDate)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get NPI frequency
     *
     * @param object $customer
     * @param datetime $currentDate
     * @return boolean
     */
    public function getNpi($customer, $currentDate)
    {
        if (!empty($customer->getCustomAttribute('joker_order_npi_start_date'))
            && !empty($customer->getCustomAttribute('joker_order_npi_end_date'))) {
            $jokerOrderNpiStartDate = $customer->getCustomAttribute('joker_order_npi_start_date')->getValue();
            $jokerOrderNpiEndDate = $customer->getCustomAttribute('joker_order_npi_end_date')->getValue();
            $jokerOrderNpiEndDate = date("Y-m-d 23:59:59", strtotime($jokerOrderNpiEndDate));

            if (($currentDate >= $jokerOrderNpiStartDate) && ($currentDate <= $jokerOrderNpiEndDate)) {
                return true;
            }
        }
        return false;
    }
}
