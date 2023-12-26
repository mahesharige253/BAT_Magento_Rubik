<?php
namespace Bat\Kakao\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    /**
     * Server key config path
     */
    public const CRON_ENABLED = 'kakao/kakao_templates/enabled';

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * Data Construct
     *
     * @param Context $context
     */

    public function __construct(
        Context $context
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }
    
    /**
     * Is Cron Enabled
     *
     * @return boolean
     */
    public function isCronEnabled()
    {
        return $this->getConfig(self::CRON_ENABLED);
    }

    /**
     * Get Config path
     *
     * @param string $path
     * @return string|int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
