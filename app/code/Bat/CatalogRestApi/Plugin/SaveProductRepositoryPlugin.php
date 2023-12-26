<?php
namespace Bat\CatalogRestApi\Plugin;

use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Api\CategoryLinkManagementInterface;

class SaveProductRepositoryPlugin
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

     /**
      * @var ProductRepository
      */
    protected $productRepository;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryFactory
     */
    protected $category;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $productCollectionFactory;

    /**
     * @param Request $request
     * @param ProductFactory $productFactory
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $category
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagementInterface
     * @param ProductRepository $productRepository
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Request $request,
        ProductFactory $productFactory,
        State $state,
        StoreManagerInterface $storeManager,
        CategoryFactory $category,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagementInterface,
        ProductRepository $productRepository,
        CollectionFactory $productCollectionFactory
    ) {
        $this->request = $request;
        $this->productFactory = $productFactory;
        $this->state = $state;
        $this->storeManager = $storeManager;
        $this->category = $category;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagementInterface;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Plugin- Product save before
     *
     * @param ProductRepository $subject
     * @param object $product
     * @param boolen|null $requestInfo
     * @return array
     */
    public function beforeSave(
        ProductRepository $subject,
        $product,
        $requestInfo = null
    ) {
        if ($this->state->getAreaCode() == 'webapi_rest') {
            if($this->request->getMethod() == 'PUT'){
                $requestData = $this->request->getBodyParams();
                $customAttributes = [];
                $customAttributesColumn = [];
                if (isset($requestData['product']['custom_attributes'])) {
                    $customAttributes = $requestData['product']['custom_attributes'];
                    $customAttributesColumn = array_column($customAttributes, 'attribute_code');
                }
                $this->validateIsPlpIsReturnValue($customAttributesColumn,$customAttributes);
                $productSku = '';
                if(isset($requestData['product']['sku'])){
                    $productSku = $requestData['product']['sku'];
                } else {
                    $productSku = $this->request->getParam('sku');
                }
                if($productSku != ''){
                    $updateProduct = $this->productRepository->get($productSku);
                    $isProductPriceTag = $updateProduct->getPricetagType();
                    if (in_array($isProductPriceTag, [1, 2, 3])) {
                        if(!isset($requestData['product']['status'])){
                            $product->setStatus(1);
                        }
                    }

                    if (in_array('is_return', $customAttributesColumn)) {
                        $isReturnPosition = array_search('is_return', $customAttributesColumn);
                        $isReturn = $customAttributes[$isReturnPosition]['value'];
                        if ($isReturn == 1) {
                            $product->setStatus(1);
                        }
                    }
                    if (in_array('is_plp', $customAttributesColumn)) {
                        $isPlpPosition = array_search('is_plp', $customAttributesColumn);
                        $isPlp = $customAttributes[$isPlpPosition]['value'];
                        if ($isPlp == 1) {
                            $product->setStatus(1);
                        }
                    }
                }
            }

            if (($this->request->getRequestUri() == '/rest/all/V1/products') ||
                $this->request->getRequestUri() == '/rest/all/V1/products/' ||
                $this->request->getRequestUri() == '/rest/V1/products/' ||
                $this->request->getRequestUri() == '/rest/V1/products') {
                $productFactory = $this->productFactory->create();
                $requestData = $this->request->getBodyParams();
                $customAttributes = [];
                $customAttributesColumn = [];

                $materialSku = '';
                if(!isset($requestData['product']['sku']) || $requestData['product']['sku'] == ''){
                    throw new LocalizedException(__('SKU is required to create material'));
                } else {
                    $materialSku = $requestData['product']['sku'];
                }
                $product->setUrlKey($materialSku);
                $materialExists = $this->checkIfMaterialCreated($materialSku);
                if($materialExists){
                    $product->setNameKr($product->getName());
                    $product->setName($materialExists->getName());
                    $product->setCustomAttribute('name_kr',$product->getName());
                    $product->setStatus($materialExists->getStatus());
                } else {
                    $product->setStatus(1);
                }
                if (isset($requestData['product']['custom_attributes'])) {
                    $customAttributes = $requestData['product']['custom_attributes'];
                    $customAttributesColumn = array_column($customAttributes, 'attribute_code');
                }
                $this->validateIsPlpIsReturnValue($customAttributesColumn,$customAttributes);
                if (isset($requestData['product']['attribute_set_id'])) {
                    $product->setAttributeSetId($requestData['product']['attribute_set_id']);
                } else {
                    $product->setAttributeSetId($productFactory->getDefaultAttributeSetId());
                }
                if (isset($requestData['product']['price'])) {
                    $product->setPrice($requestData['product']['price']);
                } else {
                    if (($this->getProductPrice($requestData['product']['sku']) <= 0)
                        || $this->getProductPrice($requestData['product']['sku']) == '') {
                        $product->setPrice(0);
                    }
                }
                if (!isset($requestData['product']['type_id'])) {
                    $product->setTypeId('simple');
                }
                if (in_array('pricetag_type', $customAttributesColumn)) {
                    $position = array_search('pricetag_type', $customAttributesColumn);
                    $isPriceTagType = $customAttributes[$position]['value'];
                    if (!in_array($isPriceTagType, [0,1,2,3])) {
                        throw new LocalizedException(__('Allowed values for pricetag_type is 0,1,2,3'));
                    }
                    if ($isPriceTagType == 2) {
                        $priceTagUtc = $this->getPriceTagUtc();
                        if ($priceTagUtc != '' && $requestData['product']['sku'] != $priceTagUtc) {
                            throw new LocalizedException(__(
                                'UTC already exists with sku :'.$priceTagUtc
                            ));
                        }
                    }
                    if ($isPriceTagType == 3) {
                        $firstOrderPackage = $this->getPriceTagFirstOrderPackage();
                        if ($firstOrderPackage != '' && $requestData['product']['sku'] != $firstOrderPackage) {
                            throw new LocalizedException(__(
                                'First Order Package already exists with sku :'.$firstOrderPackage
                            ));
                        }
                    }
                    if ($isPriceTagType) {
                        if($materialExists){
                            $materialStatus = $materialExists->getStatus();
                            if($materialStatus == 2){
                                $product->setStatus(2);
                            } else {
                                $product->setStatus(1);
                            }
                        } else{
                            $product->setStatus(2);
                        }
                        $product->setVisibility(1);
                        $stockData = $product->getStockData();
                        $stockData['manage_stock'] = 0;
                        $stockData['use_config_manage_stock'] = 0;
                        $product->setStockData($stockData);
                    } else {
                        $stockData = $product->getStockData();
                        $stockData['manage_stock'] = 1;
                        $stockData['use_config_manage_stock'] = 1;
                        $product->setStockData($stockData);
                        $product->setVisibility(4);
                    }
                } else {
                    if (!isset($requestData['product']['visibility'])) {
                        $stockData = $product->getStockData();
                        $stockData['manage_stock'] = 1;
                        $stockData['use_config_manage_stock'] = 1;
                        $product->setStockData($stockData);
                        $product->setVisibility(4);
                    }
                }
            }
        }
        $product->setStoreId(0);
        return [$product, $requestInfo];
    }

    /**
     * Plugin: After Save Product
     *
     * @param ProductRepository $subject
     * @param object $result
     * @param array $outout
     * @return array
     */
    public function afterSave(
        ProductRepository $subject,
        $result,
        $outout = null
    ) {
        if ($this->state->getAreaCode() == 'webapi_rest') {
            if (($this->request->getRequestUri() == '/rest/all/V1/products') ||
                $this->request->getRequestUri() == '/rest/all/V1/products/' ||
                $this->request->getRequestUri() == '/rest/V1/products/' ||
                $this->request->getRequestUri() == '/rest/V1/products') {
                $productFactory = $this->productFactory->create();
                $requestData = $this->request->getBodyParams();
                $customAttributes = [];
                $customAttributesColumn = [];
                $brandHouse = '';
            }
            if ($this->request->getMethod() == 'PUT') {
                /* Get category id exist or not if not then create category and assign to product */
                if ($outout->getCustomAttribute('brand_nm')) {
                    $brandName = trim($outout->getCustomAttribute('brand_nm')->getValue());
                    $sku = $outout->getSku();
                    $product = $this->productRepository->get($sku);
                    $isPriceTag = $product->getCustomAttribute('pricetag_type')->getValue();
                    if ($brandName != 'NA' && $brandName != ' ' &&
                        $isPriceTag != 1 && $isPriceTag != 2 && $isPriceTag != 3) {
                        $storeId = $this->storeManager->getWebsite(1)->getDefaultStore()->getId();
                        $parentId = $this->storeManager->getStore($storeId)->getRootCategoryId();
                        $this->state->getAreaCode();
                        $parentCategory = $this->category->create()->load($parentId);
                    //Check exist category
                        $category = $this->category->create();
                            $cate = $category->getCollection()
                                ->addAttributeToFilter('name', $brandName)
                                ->getFirstItem();

                        if (!$cate->getId()) {
                            $category->setPath($parentCategory->getPath())
                                    ->setParentId($parentId)
                                    ->setName($brandName)
                                    ->setIsActive(true);
                                $category->save();
                        }

                        $collection = $this->categoryCollectionFactory
                                    ->create()
                                    ->addAttributeToFilter('name', $brandName)
                                    ->setPageSize(1);

                        if ($collection->getSize() > 0) {
                            $categoryId = $collection->getFirstItem()->getId();
                            $this->categoryLinkManagement->assignProductToCategories(
                                $sku,
                                [$categoryId,$parentId]
                            );
                        }
                    }
                } else {
                    return $result;
                }
            }
        }
        return $result;
    }

    /**
     * Get Product Price
     *
     * @param string $sku
     * @return string|null
     */
    protected function getProductPrice($sku)
    {
        $productFactory = $this->productFactory->create();
        $product = $productFactory->loadByAttribute('sku', $sku);
        if (!empty($product)) {
            return $product->getPrice();
        }
    }

    /**
     * Check if Price Tag UTC exists
     *
     * @return string
     */
    public function getPriceTagUtc()
    {
        $priceTagUtc = '';
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('status')
            ->addFieldToFilter('pricetag_type', ['eq' => 2]);
        if ($collection->getSize()) {
            $priceTagUtc = $collection->getFirstItem()->getSku();
        }
        return $priceTagUtc;
    }

    /**
     * Check if Price Tag First Order Package exists
     *
     * @return string
     */
    public function getPriceTagFirstOrderPackage()
    {
        $firstOrderPackage = '';
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('status')
            ->addFieldToFilter('pricetag_type', ['eq' => 3]);
        if ($collection->getSize()) {
            $firstOrderPackage = $collection->getFirstItem()->getSku();
        }
        return $firstOrderPackage = $collection->getFirstItem()->getSku();
    }

    /**
     * Check If Material already created
     *
     * @param $sku
     * @return mixed
     */
    public function checkIfMaterialCreated($sku){
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('sku')
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('name_kr')
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('sku',['eq'=>$sku]);
        if($collection->getSize()){
            return $collection->getFirstItem();
        }
        return false;
    }

    /**
     * Validate Is Return and Is Plp Values
     *
     * @param $customAttributesColumn
     * @param $customAttributes
     * @throws LocalizedException
     */
    public function validateIsPlpIsReturnValue($customAttributesColumn,$customAttributes)
    {
        if (in_array('is_return', $customAttributesColumn)) {
            $isReturnPosition = array_search('is_return', $customAttributesColumn);
            $isReturn = $customAttributes[$isReturnPosition]['value'];
            if (!in_array($isReturn, [0, 1])) {
                throw new LocalizedException(__('Allowed values for is_return is 0 and 1'));
            }
        }
        if (in_array('is_plp', $customAttributesColumn)) {
            $isPlpPosition = array_search('is_plp', $customAttributesColumn);
            $isPlp = $customAttributes[$isPlpPosition]['value'];
            if (!in_array($isPlp, [0, 1])) {
                throw new LocalizedException(__('Allowed values for is_plp is 0 and 1'));
            }
        }
    }
}
