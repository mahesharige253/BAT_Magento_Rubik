<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\Rma\Model\DataProvider;

use Bat\Sales\Helper\Data as SalesHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * @class AccountClosureProductListDataProvider
 * Returns Account Closure Products List
 */
class AccountClosureProductListDataProvider
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var SalesHelper
     */
    private SalesHelper $salesHelper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param LoggerInterface $logger
     * @param SalesHelper $salesHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory   $productCollectionFactory,
        LoggerInterface  $logger,
        SalesHelper $salesHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->salesHelper = $salesHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Return Account closure product list data provider
     *
     * @return array[]
     * @throws GraphQlNoSuchEntityException
     */
    public function getAccountClosureProductList($currentPage, $pageSize)
    {
        $productArray = [];
        $count = 0;
        try {
            $productCollection = $this->salesHelper->getIroProductList();
            $productCollection->setCurPage($currentPage)->setPageSize($pageSize);
            $count = $productCollection->count();
            if ($count) {
                $items = $productCollection->getItems();
                foreach ($items as $product) {
                    try {
                        $productId = $product->getId();
                        $productArray[$productId]['short_prod_nm'] = $product->getShortProdNm();
                        $productArray[$productId]['price'] = $product->getPrice();
                        $productArray[$productId]['sku'] = $product->getSku();
                        $productArray[$productId]['id'] = $productId;
                        $productArray[$productId]['name'] = $product->getName();
                        $imageEncodeUrl = $product->getImages();
                        if ($imageEncodeUrl != '') {
                            $productImageDecode = json_decode($imageEncodeUrl);
                        }
                        if (!empty($productImageDecode) && is_array($productImageDecode)) {
                            $data = get_object_vars($productImageDecode[0]);
                            $imageEncodeUrl = base64_encode($data['fileURL']);
                        } else {
                            $imageEncodeUrl = '';
                        }
                        if ($imageEncodeUrl == '') {
                            $mediaUrl = $this->storeManager->getStore()
                                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                            $imageEncodeUrl = base64_encode($mediaUrl.'catalog/product/placeholder/default.png');
                        }
                        $productArray[$productId]['product_image'] = $imageEncodeUrl;
                    } catch (\Exception $e) {
                        $this->logger->info('Account closure product load exception : '.$e->getMessage());
                    }
                }
            } else {
                throw new GraphQlNoSuchEntityException(__('No Products Available'));
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logger->info('Account closure product list exception : '.$errorMessage);
            if ($errorMessage == 'No Products Available') {
                throw new GraphQlNoSuchEntityException(__($errorMessage));
            } else {
                throw new GraphQlNoSuchEntityException(__('Something went wrong. Please try again after sometime'));
            }
        }
        return [
            'items' => $productArray,
            'current_page' => $currentPage,
            'page_size' => $count
        ];
    }
}
