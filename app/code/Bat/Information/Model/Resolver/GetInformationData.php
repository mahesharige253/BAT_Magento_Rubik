<?php
namespace Bat\Information\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\Information\Model\InformationFormFactory;
use Bat\Information\Model\InformationBrandFormFactory;
use Bat\Information\Model\InformationFaqFormFactory;
use Bat\Information\Model\InformationNoticeFormFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Widget\Model\Template\FilterEmulate;

class GetInformationData implements ResolverInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var InformationFormFactory
     */
    protected $informationFormFactory;

    /**
     * @var InformationBrandFormFactory
     */
    protected $informationBrandFormFactory;

     /**
     * @var InformationFaqFormFactory
     */
    protected $informationFaqFormFactory;

     /**
     * @var InformationNoticeFormFactory
     */
    protected $informationNoticeFormFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @inheritdoc
     */
    public function __construct(
        InformationFormFactory $informationFormFactory,
        InformationBrandFormFactory $informationBrandFormFactory,
        InformationFaqFormFactory $informationFaqFormFactory,
        InformationNoticeFormFactory $informationNoticeFormFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface,
        FilterEmulate $widgetFilter
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->informationBrandFormFactory = $informationBrandFormFactory;
        $this->informationFaqFormFactory = $informationFaqFormFactory;
        $this->informationNoticeFormFactory = $informationNoticeFormFactory;
        $this->informationFormFactory = $informationFormFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->widgetFilter = $widgetFilter;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        if (empty($args['information_type'])) {
            return [];
        }
        $informationType = ($args['information_type']);
        if ($informationType != 'notice' && $informationType != 'faq' && $informationType != 'brand' && $informationType != 'product') {
            return [];
        }
        try {
            if($informationType == 'notice') {
            $collection = $this->informationNoticeFormFactory->create()->getCollection();
            $collection->addFieldToFilter('enable_link', 'Enabled');
            $collection->setOrder('position', 'ASC');
            $records = $collection->getData();
            if (count($records) > 0) {
                $informationData = [];
                foreach ($records as $data) {
                    $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $arr = [
                        'information_title' => $data['information_title'],
                        'id' => $data['id'],
                        'content' => $this->widgetFilter->filterDirective($data['content']),
                        'created_date' => $this->timezoneInterface->date($data['created_at'])->format('Y/m/d')
                    ];
                    $informationData[] = $arr;
                }
                return $informationData;
            } else {
                    return [];
            }
        }
        elseif($informationType == 'faq') {
            $collection = $this->informationFaqFormFactory->create()->getCollection();
            $collection->addFieldToFilter('enable_link', 'Enabled');
            $collection->setOrder('position', 'ASC');
            $records = $collection->getData();
            if (count($records) > 0) {
                $informationData = [];
                foreach ($records as $data) {
                    $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $arr = [
                        'information_title' => $data['information_title'],
                        'id' => $data['id'],
                        'content' => $this->widgetFilter->filterDirective($data['content']),
                        'created_date' => $this->timezoneInterface->date($data['created_at'])->format('Y/m/d')
                    ];
                    $informationData[] = $arr;
                }
                return $informationData;
            } else {
                    return [];
            }

        }
        elseif($informationType == 'brand') {
            $collection = $this->informationBrandFormFactory->create()->getCollection();
            $collection->addFieldToFilter('enable_link', 'Enabled');
            $collection->setOrder('position', 'ASC');
            $records = $collection->getData();
            if (count($records) > 0) {
                $informationData = [];
                foreach ($records as $data) {
                    $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $arr = [
                        'information_title' => $data['information_title'],
                        'id' => $data['id'],
                        'brand_image' => $data['brandimage'] == '' ? $data['brandimage'] : base64_encode($imgUrl . $data['brandimage']),
                        'position' => $data['position']
                    ];
                    $informationData[] = $arr;
                }
                return $informationData;
            } else {
                    return [];
            }

        }
        elseif($informationType == 'product') {
            $collection = $this->informationFormFactory->create()->getCollection();
            $collection->addFieldToFilter('enable_link', 'Enabled');
            $collection->setOrder('position', 'ASC');
            $records = $collection->getData();
            if (count($records) > 0) {
                $informationData = [];
                foreach ($records as $data) {
                    $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $arr = [
                        'information_title' => $data['information_title'],
                        'id' => $data['id'],
                        'consumer_price' => $data['consumer_price'],
                        'brand_type' => $data['brand_type'],
                        'product_image' => $data['productimage'] == '' ? $data['productimage'] : base64_encode($imgUrl . $data['productimage']),
                        'packbarcode_image' => $data['packbarcode'] == '' ? $data['packbarcode'] : base64_encode($imgUrl . $data['packbarcode']),
                        'cartonbarcode_image' => $data['cartonbarcode'] == '' ? $data['cartonbarcode'] : base64_encode($imgUrl . $data['cartonbarcode']),
                        'position' => $data['position']
                    ];
                    $informationData[] = $arr;
                }
                return $informationData;
            } else {
                    return [];
            }
        }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }
}
