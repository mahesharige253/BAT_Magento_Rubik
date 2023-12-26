<?php

namespace Navigate\BannerSlider\Block\Bannerslider;

use Magento\Framework\View\Element\Template;

class Index extends \Magento\Framework\View\Element\Template
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Collection
     */
    protected $sliderCollection;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * Intialize constructor
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Collection $sliderCollection
     * @param StoreManagerInterface $storeManagerInterface
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Navigate\BannerSlider\Model\ResourceModel\Bannerslider\Collection $sliderCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->sliderCollection = $sliderCollection;
        $this->storeManagerInterface = $storeManagerInterface;
        parent::__construct($context, $data);
    } //end __construct()

    /**
     * GetSliderCollection function
     */
    public function getSliderCollection()
    {
        return $this->sliderCollection->addFieldToFilter('status', 'Enabled')->setOrder('position', 'asc');
    } //end getSliderCollection()

    /**
     * GetMediaUrl function
     */
    public function getMediaUrl()
    {
        return $this->storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    } //end getMediaUrl()

    /**
     * GetSystemConfig function

     * @param string $path
     */
    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        $stausVal = $this->scopeConfig->getValue($path, $storeScope);
        return $stausVal;
    } //end getSystemConfig()
} //end class
