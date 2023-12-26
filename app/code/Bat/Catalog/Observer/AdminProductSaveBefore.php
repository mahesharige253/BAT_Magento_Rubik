<?php
namespace Bat\Catalog\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * @class AdminProductSaveBefore
 * Event for product save before admin
 */
class AdminProductSaveBefore implements ObserverInterface
{

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        RequestInterface $request
    ) {
        $this->productRepository = $productRepository;
        $this->request = $request;
    }

    /**
     * Before save product from admin
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $postValue = $this->request->getPostValue();
        $this->updateNewProductTag($postValue);
    }

    /**
     * Update New Product Tag
     *
     * @param array $postValue
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateNewProductTag($postValue)
    {
        if (isset($postValue['product']['current_product_id'])) {
            $productId = $postValue['product']['current_product_id'];
            if (isset($postValue['product']['product_tag'])) {
                $product = $this->productRepository->getById($productId);
                $productTagPrevious = $product->getCustomAttribute('product_tag');
                if ($productTagPrevious) {
                    $productTagCurrent = $postValue['product']['product_tag'];
                    $productTagPrevious = explode(',', $productTagPrevious->getValue());
                    if (in_array(1, $productTagPrevious)) {
                        $productTags[] = 1;
                        if (isset($productTagCurrent[1])) {
                            $productTags[] = $productTagCurrent[1];
                        } else {
                            if (isset($productTagCurrent[0])) {
                                $productTags[] = $productTagCurrent[0];
                            }
                        }
                        $postValue['product']['product_tag'] = $productTags;
                        $this->request->setPostValue($postValue);
                    }
                }
            }
        }
    }
}
