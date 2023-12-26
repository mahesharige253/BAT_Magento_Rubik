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

/**
 * DecryptData  resolver
 */
class DecryptData implements ResolverInterface
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
     *
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Data $data
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Data $data
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['encrypted_data'])) {
            throw new GraphQlInputException(__('"encrypted_data" value should be specified'));
        }
        $encryptData = $args['input']['encrypted_data'];
        $decryptedData = $this->data->decryptData($encryptData);
        return [
            'decrypted_data' => $decryptedData,
            'success' => $decryptedData ? true : false
        ];
    }
}
