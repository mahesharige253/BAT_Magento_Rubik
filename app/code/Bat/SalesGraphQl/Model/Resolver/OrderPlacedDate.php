<?php
declare(strict_types=1);

namespace Bat\SalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class OrderPlacedDate implements ResolverInterface
{
    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        TimezoneInterface $timezoneInterface
    ) {
        $this->timezoneInterface = $timezoneInterface;
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
        if (isset($value['model']) && $value['model'] !='') {
            $createdAt = $value['model']->getCreatedAt();
            return $this->timezoneInterface->date($createdAt)->format('Y-m-d H:i:s');
        }
    }
}
