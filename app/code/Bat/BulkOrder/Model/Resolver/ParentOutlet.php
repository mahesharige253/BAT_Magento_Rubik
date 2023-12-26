<?php
declare(strict_types=1);

namespace Bat\BulkOrder\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\BulkOrder\Model\ParentChildOutletCsv;

class ParentOutlet implements ResolverInterface
{

    /**
     * @var ParentChildOutletCsv
     */
    protected $parentChildOutletCsv;

    /**
     *
     * @param ParentChildOutletCsv $parentChildOutletCsv
     */
    public function __construct(
        ParentChildOutletCsv $parentChildOutletCsv
    ) {
        $this->parentChildOutletCsv = $parentChildOutletCsv;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $parentOutletId = $args['parentOutletId'];
        $outlet = $this->parentChildOutletCsv->isParentOutlet($parentOutletId);
        if ($outlet) {
            return ['success' => true];
        } else {
            return ['success' => false];
        }
    }
}
