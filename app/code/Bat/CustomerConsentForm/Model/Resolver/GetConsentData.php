<?php
namespace Bat\CustomerConsentForm\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\CustomerConsentForm\Model\ConsentFormFactory;

class GetConsentData implements ResolverInterface
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
     * @var ScopeConfigInterface
     */
    protected $consentFormFactory;

    /**
     * @inheritdoc
     */
    public function __construct(
        ConsentFormFactory $consentFormFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->consentFormFactory = $consentFormFactory;
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
        if (empty($args['input']['consent_type'])) {
            throw new GraphQlInputException(__('"consent_type" value should be specified'));
        }
        $consentType = ($args['input']['consent_type']);
        
        try {
            $collection = $this->consentFormFactory->create()->getCollection();
            $collection->addFieldToFilter('consent_type', ['finset' => $consentType]);

            $records = $collection->getData();
            if (count($records) > 0) {
                $ConsentData = [];
                foreach ($records as $data) {
                    $arr = [
                        'consent_title' => $data['title'],
                        'identifier' => $data['identifier'],
                        'link_status' => $data['enable_link'],
                        'consent_type' => $data['consent_type'],
                        'popup_title' => $data['linktitle'],
                        'content' => $data['content'],
                        'consent_required' => $data['consent_required'],
                        'position' => $data['position'],
                        'validate_message' => $data['validation']
                    ];
                    $ConsentData[] = $arr;
                }
                return $ConsentData;
            } else {
                    throw new GraphQlNoSuchEntityException(__('There is no ConsentForm data'));
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }
}
