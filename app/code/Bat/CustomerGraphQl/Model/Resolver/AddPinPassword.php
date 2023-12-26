<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\CustomerGraphQl\Model\OutletIdPinPasswordCheck;

class AddPinPassword implements ResolverInterface
{
    /**
     * @var OutletIdPinPasswordCheck
     */
    private $outletIdPinPasswordCheck;

    /**
     * @param OutletIdPinPasswordCheck $outletIdPinPasswordCheck
     */
    public function __construct(
        OutletIdPinPasswordCheck $outletIdPinPasswordCheck
    ) {
        $this->outletIdPinPasswordCheck = $outletIdPinPasswordCheck;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        if (
            isset($args['input']['pin'])
            && !preg_match("/^[0-9]+$/", $args['input']['pin'])
        ) {
            throw new GraphQlInputException(__('Pin is not valid'));
        }

        return $this->outletIdPinPasswordCheck->execute($args['input']);
    }
}
