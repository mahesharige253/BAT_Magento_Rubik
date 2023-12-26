<?php
namespace Bat\PasswordHistory\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const DEFAULT_MESSAGE = 'Please choose a pin and password that you haven\'t used before.';
    public const DEFAULT_HISTORY_SIZE = 10;
    public const DEFAULT_ALLOWE_HOURS = 24;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Is Enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->config->getValue(
            'bat_customer/password_history/history_enabled',
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage()
    {
        $message = $this->config->getValue(
            'bat_customer/password_history/history_message',
            ScopeInterface::SCOPE_STORES
        );
        return trim($message) ?: self::DEFAULT_MESSAGE;
    }

    /**
     * Get History Size
     *
     * @return int
     */
    public function getHistorySize()
    {
        $historySize = (int) $this->config->getValue(
            'bat_customer/password_history/history_size',
            ScopeInterface::SCOPE_WEBSITES
        );
        return $historySize ?: self::DEFAULT_HISTORY_SIZE;
    }

    /**
     * Get Allowed Hours
     *
     * @return int
     */
    public function getAllowedHours()
    {
        $allowedHours = (int) $this->config->getValue(
            'bat_customer/password_history/allowed_hours',
            ScopeInterface::SCOPE_WEBSITES
        );
        return $allowedHours ?: self::DEFAULT_ALLOWE_HOURS;
    }
}
