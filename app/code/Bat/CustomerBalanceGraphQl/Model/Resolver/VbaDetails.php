<?php
declare(strict_types=1);

namespace Bat\CustomerBalanceGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

class VbaDetails implements ResolverInterface
{

    /**
     * @var Config
     */
    private $_eavConfig;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * Construct method
     *
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        GetCustomer $getCustomer
    ) {
        $this->_eavConfig = $eavConfig;
        $this->getCustomer = $getCustomer;
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
        $customer = $this->getCustomer->execute($context);
        $firstname = $customer->getFirstname();
        $accountno = $customer->getCustomAttribute('virtual_account')->getValue();
        $accountholder = $firstname;
        $bankCode = $customer->getCustomAttribute('virtual_bank')->getValue();
        $bankName = $this->getAttributeLabelByValue('virtual_bank', 'customer', $bankCode);
        $vbadetails = ['bank_details' => [
            'bank_name' => $bankName,
            'bank_code' => $bankCode
        ],
        'account_holder_name' => $accountholder,
        'account_number' => $accountno
        ];
        return $vbadetails;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabelByValue($attributeCode, $entityType, $value)
    {
        try {
            $entityType = $this->_eavConfig->getEntityType($entityType);
            $attribute = $this->_eavConfig->getAttribute($entityType, $attributeCode);
            $options = $attribute->getSource()->getAllOptions();
            foreach ($options as $option) {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
