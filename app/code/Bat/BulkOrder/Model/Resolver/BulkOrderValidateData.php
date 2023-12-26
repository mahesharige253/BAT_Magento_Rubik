<?php

namespace Bat\BulkOrder\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Bat\BulkOrder\Model\ValidateCSVdata;
use Bat\BulkOrder\Model\CreateBulkCart;

/**
 * Bulk Order Validate process
 */
class BulkOrderValidateData implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * @var GetCustomer
     */
    protected $getCustomer;

    /**
     * @var ValidateCSVdata
     */
    protected $validateData;

    /**
     * @var CreateBulkCart
     */
    protected $bulkOrderData;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param GetCustomer $getCustomer
     * @param ValidateCSVdata $validateData
     * @param CreateBulkCart $bulkCartdata
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        GetCustomer $getCustomer,
        ValidateCSVdata $validateData,
        CreateBulkCart $bulkCartdata
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->getCustomer = $getCustomer;
        $this->validateData = $validateData;
        $this->bulkOrderData = $bulkCartdata;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $orderItems = $args['orderItems'];
        $data = [];

        $validStatus = $this->validateData->execute($orderItems);

        $validSuccessData = $validStatus['status'];
        if ($validSuccessData == 'failed') {
            $data['success'] = false;
            $data['error_message'] = $validStatus['message'];
            $data['bulkorder_data'] =[];
        } else {
            $data['success'] = true;
            $data['error_message'] = [];
            $bulkOrderCarts = $this->bulkOrderData->createCart($orderItems);
            $i = 0;
            foreach ($bulkOrderCarts as $key => $value) {
                $data['bulkorder_data'][$i]['outlet_id'] = $key;
                $data['bulkorder_data'][$i]['masked_cart_id'] = $value;
                $i++;
            }
        }
        
        return $data;
    }
}
