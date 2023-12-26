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
use Bat\Information\Model\InformationOrderManualFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class GetOrderManualData implements ResolverInterface
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
     * @var InformationOrderManualFactory
     */
    protected $informationOrderManualFactory;



    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @inheritdoc
     */
    public function __construct(
        InformationOrderManualFactory $informationOrderManualFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->informationOrderManualFactory = $informationOrderManualFactory;
        $this->timezoneInterface = $timezoneInterface;
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
        try {
            $collection = $this->informationOrderManualFactory->create()->getCollection();
            $collection->addFieldToFilter('enable_link', 'Enabled');
            $records = $collection->getData();
            if (count($records) > 0) {
                foreach ($records as $data) {
                    $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $arr = [
                        'title' => $data['information_title'],
                        'pdf' => $data['orderpdf'] == '' ? '' : $imgUrl.$data['orderpdf'],
                        'banner_image' => $data['ordermanualbanner'] == '' ? $data['ordermanualbanner'] : base64_encode($imgUrl . $data['ordermanualbanner']),
                    ];
                }
                return $arr;
            } else {
                    return [];
            }

        
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }
}

