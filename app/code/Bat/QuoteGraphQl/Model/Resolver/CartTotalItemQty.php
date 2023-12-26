<?php
namespace Bat\QuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;

class CartTotalItemQty implements ResolverInterface
{
   /**
    * @var CartRepositoryInterface
    */
    protected $cartRepositoryInterface;

    /**
     * @param CartRepositoryInterface $cartRepositoryInterface
     */
    public function __construct(
        CartRepositoryInterface $cartRepositoryInterface
    ) {
        $this->cartRepositoryInterface = $cartRepositoryInterface;
    }
    
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Quote $cart */
        $cartData = $value['model'];
        $cartId = $cartData->getId();
        $cart = $this->cartRepositoryInterface->get($cartId);
        $qty = 0;
        foreach ($cart->getAllItems() as $item) {
            if (!($item->getIsPriceTag())) {
                $qty += $item->getQty();
            }
        }
        return $qty;
    }
}
