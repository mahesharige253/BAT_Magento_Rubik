<?php
namespace Bat\ContactUs\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\ContactUs\Model\ContactUsFormFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class GetContactUsData implements ResolverInterface
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
     * @var ContactUsFormFactory
     */
    protected $contactusFormFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezoneInterface;

    /**
     * @inheritdoc
     */
    public function __construct(
        ContactUsFormFactory $contactusFormFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TimezoneInterface $timezoneInterface
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->contactusFormFactory = $contactusFormFactory;
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
            $collection = $this->contactusFormFactory->create()->getCollection();
            $records = $collection->getData();
            if (count($records) > 0) {
                foreach ($records as $data) {
                    $imgUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                    $arr = [
                        'page_title' => $data['page_title'],
                        'banner_image' => $data['bannerimage'] == '' ? $data['bannerimage'] : base64_encode($imgUrl . $data['bannerimage']),
                        'operating_hours_label' => $data['operating_hours_label'],
                        'operating_hours_value' => $data['operating_hours_value'],
                        'operating_hours_value_2' => $data['operating_hours_value_two'],
                        'contact_number' => $data['contact_number'],
                        'company_name_label' => $data['company_name_label'],
                        'company_name_value' => $data['company_name_value'],
                        'company_address_label' => $data['company_address_label'],
                        'company_address_value' => $data['company_address_value'],
                        'business_license_label' => $data['business_license_label'],
                        'business_license_value' => $data['business_license_value'],
                        'representative_label' => $data['representative_label'],
                        'representative_value' => $data['representative_value'],
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
