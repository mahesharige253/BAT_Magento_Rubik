<?php
namespace Bat\PasswordHistory\Api;

use Magento\Framework\Exception\LocalizedException;

interface UsedPasswordManagementInterface
{
    /**
     * ValidatePassword
     *
     * @param array $customerData
     * @return bool
     * @throws LocalizedException
     */
    public function validatePassword($customerData);

    /**
     * ValidatePin
     *
     * @param array $customerData
     * @return bool
     * @throws LocalizedException
     */
    public function validatePin($customerData);

    /**
     * SaveUsedPinPassword
     *
     * @param array $customerData
     */
    public function saveUsedPinPassword($customerData);
}
