<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Bat\Integration\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\CustomerGraphQl\Helper\Data as CustomerData;
use Magento\Framework\App\ResourceConnection;
use Bat\Customer\Model\ChangeAddressFactory;


/**
 * DecryptData  resolver
 */
class IsLinkValid implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    private $_productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @var Data
     */
    private $data;

    /**
     * @var CustomerData
     */
    private $customerData;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ChangeAddressFactory
     */
    private $changeAddressFactory;

    /**
     *
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Data $data
     * @param CustomerData $customerData
     * @param ChangeAddressFactory $changeAddressFactory
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Data $data,
        CustomerData $customerData,
        ResourceConnection $resource,
        ChangeAddressFactory $changeAddressFactory

    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->data = $data;
        $this->customerData = $customerData;
        $this->resource = $resource;
        $this->changeAddressFactory = $changeAddressFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['key_id'])) {
            throw new GraphQlInputException(__('"key_id" value should be specified'));
        }
        if (empty($args['input']['type'])) {
            throw new GraphQlInputException(__('"type" value should be specified'));
        }
        if (!in_array($args['input']['type'], ['address_change', 'registration_set_pinpassword', 'forgot_set_pinpassword'])) {
            throw new GraphQlInputException(__('"type" value is not correct'));
        }

        $keyId = $args['input']['key_id'];
        $message = '';
        $decryptKey = $this->data->decryptData($args['input']['key_id']);
        $decryptFields = explode(",", $decryptKey);
        $outletId = $decryptFields[0];
        if ($decryptFields == $outletId) {
            $keyId = '';
        }

        $currentTime = date('Y-m-d H:i:s');
        $collection = $this->changeAddressFactory->create()->getCollection();
        if ($args['input']['type'] == 'address_change') {
            $validUpto = $this->customerData->getAddressChangeUrlValidUpto();
            $collection->addFieldToFilter('outlet_id', ['finset' => $outletId])
                    ->addFieldToFilter('url_type', ['finset' => $args['input']['type']])
                    ->addFieldToFilter('urlkey', ['eq' => $keyId]);
            $records = $collection->getData();
            foreach ($records as $record) {
                $time = date('Y-m-d H:i:s', strtotime("+" . $validUpto . "hours", strtotime($record['created_at'])));
                if ($time > $currentTime) {
                    $message = ['success' => true];
                } else {
                    $model = $this->changeAddressFactory->create();
                    $model->load($record['entity_id']);
                    $model->delete();
                    $message = ['success' => false];
                }
            }
        } else if ($args['input']['type'] == 'registration_set_pinpassword') {
            $validUpto = $this->customerData->getSetPasswordPinValidUpto();
            $collection->addFieldToFilter('outlet_id', ['finset' => $outletId])
                    ->addFieldToFilter('url_type', ['finset' => $args['input']['type']])
                    ->addFieldToFilter('urlkey', ['eq' => $keyId]);
            $records = $collection->getData();
            foreach ($records as $record) {
                $time = date('Y-m-d H:i:s', strtotime("+" . $validUpto . "day", strtotime($record['created_at'])));
                if ($time > $currentTime) {
                    $message = ['success' => true];
                } else {
                    $model = $this->changeAddressFactory->create();
                    $model->load($record['entity_id']);
                    $model->delete();
                    $message = ['success' => false];
                }
            }
        } else if ($args['input']['type'] == 'forgot_set_pinpassword') {
            $validUpto = $this->customerData->getForgotPinPasswordExpiry();
            $collection->addFieldToFilter('outlet_id', ['eq' => $outletId])
                    ->addFieldToFilter('url_type', ['finset' => $args['input']['type']])
                    ->addFieldToFilter('urlkey', ['eq' => $keyId]);
            $records = $collection->getData();
            foreach ($records as $record) {
                $time = date('Y-m-d H:i:s', strtotime("+" . $validUpto . "hours", strtotime($record['created_at'])));
                if ($time > $currentTime) {
                    $message = ['success' => true];
                } else {
                    $model = $this->changeAddressFactory->create();
                    $model->load($record['entity_id']);
                    $model->delete();
                    $message = ['success' => false];
                }
            }
        }
        return $message;
    }
}