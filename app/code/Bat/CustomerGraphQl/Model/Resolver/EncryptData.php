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
class EncryptData implements ResolverInterface
{
     /**
      * @var Data
      */
    private $data;

    /**
     * @param Data $data
     */
    public function __construct(
        Data $data
    ) {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['data'])) {
            throw new GraphQlInputException(__('"encrypted_data" value should be specified'));
        }
        $data = $args['input']['data'];
        $encryptData = $this->data->encryptData($data);
        return [
            'encrypted_data' => $encryptData,
            'success' => $encryptData ? true : false
        ];
    }
}
