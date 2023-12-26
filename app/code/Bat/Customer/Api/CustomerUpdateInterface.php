<?php
namespace Bat\Customer\Api;

interface CustomerUpdateInterface
{
    /**
     * Update customer information
     *
     * @param string $batchId
     * @param string $createdAt
     * @param string $countryCode
     * @param string $companyCode
     * @param string $outletCode
     * @param string $sapOutletCode
     * @return mixed
     */
    public function updateCustomer($batchId, $createdAt, $countryCode, $companyCode, $outletCode, $sapOutletCode);
}
