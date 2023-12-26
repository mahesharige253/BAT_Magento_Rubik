<?php
namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\BulkOrder\Model\PricetagBulkAddCart;
use Bat\Customer\Helper\Data;

class AddUpdateBulkPriceTag implements ResolverInterface
{
    /**
     * @var PricetagBulkAddCart
     */
    private $pricetagBulkAddCart;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param PricetagBulkAddCart $pricetagBulkAddCart
     * @param Data $helper
     */
    public function __construct(
        PricetagBulkAddCart $pricetagBulkAddCart,
        Data $helper
    ) {
        $this->pricetagBulkAddCart = $pricetagBulkAddCart;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current customer isn\'t authorized.try agin with authorization token'
                )
            );
        }
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $childStoreOutletId = $args['input']['outlet_id'];
        $childCustomer = $this->helper->isOutletIdValidCustomer($childStoreOutletId);
        $data = $args['input'];
        unset($data['outlet_id']);
        return $this->pricetagBulkAddCart->execute($data, $childCustomer->getId(), $context);
    }
}
