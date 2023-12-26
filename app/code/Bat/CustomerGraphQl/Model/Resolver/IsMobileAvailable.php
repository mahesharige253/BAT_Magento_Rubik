<?php

declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Bat\CustomerGraphQl\Model\CustomerMobileAvailable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Is Customer Mobile Number Available
 */
class IsMobileAvailable implements ResolverInterface
{
    /**
     * @var CustomerMobileAvailable
     */
    private $customerMobileAvailable;

    /**
     * @param CustomerMobileAvailable $customerMobileAvailable
     */
    public function __construct(
        CustomerMobileAvailable $customerMobileAvailable
    ) {
        $this->customerMobileAvailable = $customerMobileAvailable;
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
        if (empty($args['input']['mobilenumber'])) {
            throw new GraphQlInputException(__('Mobile number must be specified'));
        }

//        if (!preg_match("/010 ([0-9]{3}|[0-9]{4}) [0-9]{4}$/", $args['input']['mobilenumber'])) {
//            throw new GraphQlInputException(__('Mobile number value is not valid'));
//        }
        if($args['input']['isPasswordPin'] == 1 && $args['input']['isOutletId'] == 1){
            throw new GraphQlInputException(__('Both flags cannot be true at same time'));
        }
        try {
            return $this->customerMobileAvailable->isMobileAvailable($args['input']);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
    }
}
