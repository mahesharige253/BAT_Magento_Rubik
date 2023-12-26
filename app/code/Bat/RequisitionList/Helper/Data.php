<?php
namespace Bat\RequisitionList\Helper;

/**
 * @class Data
 *
 * Helper class for new products
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Requisition List Customer config path
     */
    public const REQUISITIONLIST_ADMIN_PATH = 'requisitionlist_bat/requisitionlist/requisitionlist_admin';

    /**
     * Last number of months path
     */
    public const LAST_NUMBER_OF_MONTHS = 'requisitionlist_bat/requisitionlist/last_number_of_months';

     /**
     * Return First Order RL
     */
    public const FIRSTORDER_RL = 'requisitionlist_bat/requisitionlist/firstorder_rl';

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
        return $this->_backendUrl->getUrl('requisitionlist/requisitionlist/products', ['_current' => true]);
    }

    /**
     * Get Requisitionlist Admin allow count
     *
     * @return string
     */
    public function getRequisitionlistAdmin()
    {
        return $this->getConfig(self::REQUISITIONLIST_ADMIN_PATH);
    }

    /**
     * Get last number of months
     */
    public function getLastNumberOfMonths()
    {
        return $this->getConfig(self::LAST_NUMBER_OF_MONTHS);
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

    /**
     * Get First Order RL
     */
    public function getFirstOrderRL()
    {
        return $this->getConfig(self::FIRSTORDER_RL);
    }
}
