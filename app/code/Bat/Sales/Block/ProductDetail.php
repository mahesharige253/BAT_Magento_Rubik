<?php
namespace Bat\Sales\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductRepository;

class ProductDetail extends Template
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ProductRepository $productRepository
    ) {
        $this->_storeManager = $storeManager;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    /**
     * Get Product Image Url
     *
     * @param string $sku
     * @return string
     */
    public function getProductImageUrl($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            return __('Data not found');
        }
        $encodeUrl = '';
        if (!empty($product->getImage())) {
             $imagePath = $product->getImage();
            if($imagePath != 'no_selection' && $imagePath != '') {
                $imageUrl = $this->getMediaUrl() . $imagePath;
            }else {
                $imageUrl = $this->getMediaUrl().'/placeholder/thumbnail.png';
            }
            $encodeUrl = base64_encode($imageUrl);
        }
        return $encodeUrl;
    }

    /**
     * Get Media Url
     */
    public function getMediaUrl()
    {
        $prodPath = 'catalog/product';
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $prodPath;
    }

    /**
     * Get Default Attribute Value
     *
     * @param string $sku
     * @return string
     */
    public function getDefaultAttributeValue($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            return __('Data not found');
        }
        $defaultTextAttributeVal = '';
        $attributeLabel = '';
        $selectedAttributeVal = '';
        $attributeCode = $product->getBatDefaultAttribute();
        $attribute = $product->getResource()->getAttribute($attributeCode);
        if ($attribute) {
            if (
                in_array(
                    $product->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                    ['select']
                ) ||
                in_array(
                    $product->getResource()->getAttribute($attributeCode)->getFrontendInput(),
                    ['boolean']
                )
            ) {
                $selectedAttributeVal = $product->getAttributeText($attributeCode);
                $attributeLabel = $product->getResource()->getAttribute($attributeCode)->
                    getFrontendLabel();
            } else {
                $attributeLabel = $product->getResource()->getAttribute($attributeCode)->
                    getFrontendLabel();
                $selectedAttributeVal = $product->getData($attributeCode);
            }
        }
        if ($selectedAttributeVal != '') {
            $defaultTextAttributeVal = $attributeLabel . ': ' . $selectedAttributeVal;
        }
        return $defaultTextAttributeVal;
    }
}