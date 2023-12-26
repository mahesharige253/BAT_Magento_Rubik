<?php

namespace Bat\Danal\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\Danal\Model\ConfirmDanal;

class Confirm implements ResolverInterface
{
    /**
     * @var ConfirmDanal
     */
    private $confirmDanal;

    /**
     * @inheritdoc
     *
     * @param ConfirmDanal $confirmDanal
     */
    public function __construct(
        ConfirmDanal $confirmDanal
    ) {
        $this->confirmDanal = $confirmDanal;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['tid'])) {
            throw new GraphQlInputException(__('"TID" is mandatory.'));
        }

        return $this->confirmDanal->danalConfirmation($args['tid']);
    }
}
