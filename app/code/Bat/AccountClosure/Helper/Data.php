<?php
namespace Bat\AccountClosure\Helper;

/**
 * @class Data
 *
 * Helper class for new products
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var getScopeConfig
     */
    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_backendUrl = $backendUrl;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Get products tab Url in admin
     *
     * @return string
     */
    public function getProductsGridUrl()
    {
        return $this->_backendUrl->getUrl('accountclosure/accountclosure/products', ['_current' => true]);
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
