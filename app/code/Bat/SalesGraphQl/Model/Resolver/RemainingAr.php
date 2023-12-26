<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\CustomerBalanceGraphQl\Helper\Data;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

class RemainingAr implements ResolverInterface
{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var PriceHelper
     */ 
    protected $priceHelper;

    /**
     * @param Data $helper
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        Data $helper,
        PriceHelper $priceHelper
    ) {
        $this->helper = $helper;
        $this->priceHelper = $priceHelper;
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
        $customerId = $context->getUserId();
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Please specify a valid customer'));
        }
        $order = $value['model'];
        if($order->getRemainingAr() > 0){
            return $this->priceHelper->currency($order->getRemainingAr(), false, false);
        }else{
            return $this->helper->getRemainingArLimit($customerId);
        }
    }
}
