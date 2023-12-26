<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ProductRepository;
use Psr\Log\LoggerInterface;
use Bat\BestSellers\Model\GetBestSellers;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;

/**
 * @class ProductTag
 * return ProductTag attribute value
 */
class ProductTag implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var GetBestSellers
     */
    private GetBestSellers $getBestSellers;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @var GetCustomer
     */
    private GetCustomer $getCustomer;

    /**
     * @param ProductRepository $productRepository
     * @param LoggerInterface $logger
     * @param GetBestSellers $getBestSellers
     * @param TimezoneInterface $timezoneInterface
     * @param GetCustomer $getCustomer
     */
    public function __construct(
        ProductRepository $productRepository,
        LoggerInterface $logger,
        GetBestSellers $getBestSellers,
        TimezoneInterface $timezoneInterface,
        GetCustomer $getCustomer
    ) {
        $this->_productRepository = $productRepository;
        $this->logger = $logger;
        $this->getBestSellers = $getBestSellers;
        $this->timezoneInterface = $timezoneInterface;
        $this->getCustomer = $getCustomer;
    }

    /**
     * Resolver for Product Tags
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return false[]
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        \Magento\Framework\GraphQl\Schema\Type\ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $frequent = false;
        $hot = false;
        $newProductCarouselRequest = false;
        if (isset($value['newproducts_carousel'])) {
            $newProductCarouselRequest = true;
        }
        $product = $value['model'];
        if (isset($value['frequent'])) {
            $frequent = $value['frequent'];
        }
        if (isset($value['best_seller'])) {
            $hot = $value['best_seller'];
        }

        $data = $product->getProductTag();
        $result = [
            'new' => false,
            'limited' => false,
            'hot' => false,
            'frequent' => false
        ];
        if ($data != '') {
            $data = explode(',', $data);
            foreach ($data as $value) {
                if ($value == 1) {
                    $result['new'] = true;
                }
                if ($value == 2) {
                    $result['limited'] = true;
                }
            }
        }
        if ($newProductCarouselRequest) {
            if ($hot == $product->getId()) {
                $result['hot'] = true;
            }
        } else {
            $customer = $this->getCustomer->execute($context);
            if($customer->getCustomAttribute('sigungu_code')) {
                $sigunguCode = $customer->getCustomAttribute('sigungu_code')->getValue();
                if($sigunguCode != '') {
                    $isBestSellers= $this->getBestSellers->isProductBestSellers($sigunguCode, $product->getId());
                    if ($isBestSellers) {
                        $result['hot'] = true;
                    }
                }
            }
        }
        if ($frequent == $product->getId()) {
            $result['frequent'] = true;
        }
        return $result;
    }
}
