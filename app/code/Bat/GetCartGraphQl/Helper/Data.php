<?php
namespace Bat\GetCartGraphQl\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    /**
     * Server key config path
     */
    public const MINIMUM_QTY_CART_PATH = 'general_settings/general/minimum_qty_per_cart';
    
    /**
     * Default config path
     */
    public const MAXIMUM_QTY_CART_PATH = 'general_settings/general/maximum_qty_per_cart';

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
     * Get Minimum Cart Qty
     *
     * @return int
     */
    public function getMinimumCartQty()
    {
        return $this->getConfig(self::MINIMUM_QTY_CART_PATH);
    }

    /**
     * Get Maximum Cart Qty
     *
     * @return int
     */
    public function getMaximumCartQty()
    {
        return $this->getConfig(self::MAXIMUM_QTY_CART_PATH);
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
