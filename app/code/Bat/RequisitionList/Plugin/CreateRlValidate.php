<?php

namespace Bat\RequisitionList\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\RequisitionListGraphQl\Model\Resolver\CreateRequisitionList;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class CreateRlValidate
{
    /**
     * Before remove product from RL
     *
     * @param CreateRequisitionList $subject
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function beforeResolve(
        CreateRequisitionList $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (preg_match('/[!@#\/$%^&*()_+{}\[\]:;<>,?~`]/', $args['input']['name'])) {
            throw new GraphQlInputException(__('Please Enter a valid RL title.'));
        }
        return [$field, $context, $info, $value, $args];
    }
}