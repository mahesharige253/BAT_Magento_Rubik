<?php

namespace Bat\Danal\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Bat\Danal\Model\AuthenticateDanal;

class Authenticate implements ResolverInterface
{
    /**
     * @var AuthenticateDanal
     */
    private $authenticateDanal;

    /**
     * @inheritdoc
     *
     * @param AuthenticateDanal $authenticateDanal
     */
    public function __construct(
        AuthenticateDanal $authenticateDanal
    ) {
        $this->authenticateDanal = $authenticateDanal;
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
        if (!isset($args['targetQuery']) && !isset($args['backQuery'])) {
            throw new GraphQlInputException(__('"targetQuery & backQuery" params are mandatory.'));
        }

        if (!isset($args['targetQuery'])) {
            throw new GraphQlInputException(__('"targetQuery" param is mandatory.'));
        }

        if (!isset($args['backQuery'])) {
            throw new GraphQlInputException(__('"backQuery" param is mandatory.'));
        }

        return $this->authenticateDanal->authenticate($args['targetQuery'], $args['backQuery']);
    }
}
