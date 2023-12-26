<?php
namespace Bat\PriceTagsGraphQl\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Store\Model\StoreManagerInterface;

class OrderPriceTagList
{
    
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductFactory $productFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get price tag items
     *
     * @param array $data
     * @throws GraphQlInputException
     * @return array
     */
    public function execute($data)
    {
        $order = $this->orderRepository->get($data['orderId']);
        $priceTagItems = [];
        foreach ($order->getAllVisibleItems() as $_item) {
            try {
                if ($_item->getProduct() && $_item->getIsPriceTag()) {
                    $itemProductId = $_item->getProduct()->getId();
                    $product = $this->productFactory->create()->load($itemProductId);

                    $imageEncodeUrl = "";
                    $customAttributeValue = $product->getData('images');
                    if ($customAttributeValue != '') {
                        $productImageDecode = json_decode($customAttributeValue);
                    }
                    if (!empty($productImageDecode) && is_array($productImageDecode)) {
                        $data = get_object_vars($productImageDecode[0]);
                        $imageEncodeUrl = base64_encode($data['fileURL']);
                    }
                    if ($imageEncodeUrl == '') {
                        $mediaUrl = $this->storeManager->getStore()
                            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                        $imageEncodeUrl = base64_encode($mediaUrl.'catalog/product/placeholder/default.png');
                    }

                    $priceTagItems[] = [
                        'priceTagImage' => $imageEncodeUrl, //$_item->getProductImage(),
                        'priceTagName' => $_item->getName(),
                        'priceTagSku' => $_item->getSku()
                    ];
                }
            } catch (Exception $e) {

            }
        }
        return $priceTagItems;
    }
}
