<?php
declare(strict_types=1);

namespace Bat\JokerOrder\Helper\Customer\Edit\Action;

/**
 * Adminhtml catalog product action attribute update helper.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Attribute extends \Magento\Backend\Helper\Data
{
    /**
     * Selected customers for mass-update
     *
     * @var \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    protected $_customers;

    /**
     * Array of same attributes for selected customers
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    protected $_attributes;

    /**
     * Excluded from batch update attribute codes
     *
     * @var array
     */
    protected $_excludedAttributes = ['url_key'];

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Route\Config $routeConfig
     * @param \Magento\Framework\Locale\ResolverInterface $locale
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Backend\App\Area\FrontNameResolver $frontNameResolver
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Route\Config $routeConfig,
        \Magento\Framework\Locale\ResolverInterface $locale,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Backend\App\Area\FrontNameResolver $frontNameResolver,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Backend\Model\Session $session,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_eavConfig = $eavConfig;
        $this->_session = $session;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $routeConfig, $locale, $backendUrl, $auth, $frontNameResolver, $mathRandom);
    }

    /**
     * Return customer collection with selected customer filter
     *
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    public function getCustomers()
    {
        if ($this->_customers === null) {
            $customersIds = $this->getCustomerIds();

            if (!is_array($customersIds)) {
                $customersIds = [0];
            }

            $this->_customers = $this->_customerFactory->create()->setStoreId(
                $this->getSelectedStoreId()
            )->addIdFilter(
                $customersIds
            );
        }

        return $this->_customers;
    }

    /**
     * Set array of selected customer
     *
     * @param array $customerIds
     * @return void
     */
    public function setCustomerIds($customerIds)
    {
        $this->_session->setCustomerIds($customerIds);
    }

    /**
     * Return array of selected customer ids from post or session
     *
     * @return array|null
     */
    public function getCustomerIds()
    {
        return $this->_session->getCustomerIds();
    }

    /**
     * Return selected store id from request
     *
     * @return integer
     */
    public function getSelectedStoreId()
    {
        return (int)$this->_getRequest()->getParam('store', \Magento\Store\Model\Store::DEFAULT_STORE_ID);
    }

    /**
     * Return array of attribute sets by selected customers
     *
     * @return array
     */
    public function getCustomersSetIds()
    {
        return $this->getCustomers()->getSetIds();
    }

    /**
     * Return collection of same attributes for selected customers without unique
     *
     * @return \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    public function getAttributes()
    {
        if ($this->_attributes === null) {
            $this->_attributes = $this->_eavConfig->getEntityType(
                \Magento\Customer\Model\Customer::ENTITY
            )->getAttributeCollection()->addIsNotUniqueFilter()->setInAllAttributeSetsFilter(
                $this->getCustomersSetIds()
            );

            if ($excludedAttributes = $this->getExcludedAttributes()) {
                $this->_attributes->addFieldToFilter('attribute_code', ['nin' => $excludedAttributes]);
            }

            // check customer type apply to limitation and remove attributes that impossible to change in mass-update
            $customerTypeIds = $this->getCustomers()->getCustomerTypeIds();
            foreach ($this->_attributes as $attribute) {
                /* @var $attribute \Magento\Customer\Model\Entity\Attribute */
                foreach ($customerTypeIds as $customerTypeId) {
                    $applyTo = $attribute->getApplyTo();
                    if (count($applyTo) > 0 && !in_array($customerTypeId, $applyTo)) {
                        $this->_attributes->removeItemByKey($attribute->getId());
                        break;
                    }
                }
            }
        }

        return $this->_attributes;
    }

    /**
     * Gets website id.
     *
     * @param int $storeId
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreWebsiteId($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getWebsiteId();
    }

    /**
     * Retrieve excluded attributes.
     *
     * @return array
     */
    public function getExcludedAttributes(): array
    {
        return $this->_excludedAttributes;
    }
}
