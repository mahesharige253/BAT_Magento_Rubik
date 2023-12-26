<?php
namespace Bat\CustomerBalance\Api;

interface CustomerCreditUpdateInterface
{
    /**
     * Update customer credit details
     *
     * @param string $sapOutletCode
     * @param string $outletId
     * @param string $creditLimit
     * @param string $availableCreditLimit
     * @param string $creditExposure
     * @param string $overdueFlag
     * @param string $overdueAmount
     * @return mixed
     */
    public function updateCustomerCredit(
        $sapOutletCode,
        $outletId,
        $creditLimit,
        $availableCreditLimit,
        $creditExposure,
        $overdueFlag,
        $overdueAmount
    );
}
