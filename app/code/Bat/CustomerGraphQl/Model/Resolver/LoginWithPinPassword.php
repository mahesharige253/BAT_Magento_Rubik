<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\CustomerGraphQl\Model\ValidatePinPassword;

class LoginWithPinPassword implements ResolverInterface
{
    /**
     * @var ValidatePinPassword
     */
    private $validatePinPassword;

    /**
     * @param ValidatePinPassword $validatePinPassword
     */
    public function __construct(
        ValidatePinPassword $validatePinPassword
    ) {
        $this->validatePinPassword = $validatePinPassword;
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

        /* Device ID is mandatory */
        if (!isset($args['input']['device_id']) || $args['input']['device_id'] == '') {
            throw new GraphQlInputException(__('Device ID is mandatory.'));
        }
        return $this->validatePinPassword->loginCustomerWithPinPassword($args['input']);
    }
}
