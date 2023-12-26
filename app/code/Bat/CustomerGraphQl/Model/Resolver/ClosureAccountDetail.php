<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;
use Bat\AccountClosure\Model\AccountClosureProductReturn;
use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;

class ClosureAccountDetail implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Session
     */
    private $eavConfig;

    /**
     * @var AccountClosureProductReturn
     */
    protected $accountClosureProductReturn;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param GetCustomer $getCustomer
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $eavConfig
     * @param AccountClosureProductReturn $accountClosureProductReturn
     * @param ProductRepository $productRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetCustomer $getCustomer,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig,
        AccountClosureProductReturn $accountClosureProductReturn,
        ProductRepository $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->getCustomer = $getCustomer;
        $this->scopeConfig = $scopeConfig;
        $this->eavConfig = $eavConfig;
        $this->accountClosureProductReturn = $accountClosureProductReturn;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $accountDisclosureApprovalStatus = '';
        $accountDisclosureApprovalStatusLabel = '';
        $accountDisclosureRejectedReason = '';
        $accountDisclosureRejectedFields = '';
        $statusMessage = '';
        $accountClosingDate = '';

        if ($customer->getCustomAttribute('approval_status')) {
            $accountDisclosureApprovalStatus = $customer->getCustomAttribute('approval_status')->getValue();
            if ($accountDisclosureApprovalStatus !='') {
                $statusLabel = $this->getCustomAttributeOptionLabel(
                    'approval_status',
                    $accountDisclosureApprovalStatus
                );
                $accountDisclosureApprovalStatusLabel = $statusLabel->getText();
            }
        }
        if ($customer->getCustomAttribute('disclosure_rejected_fields')) {
            $accountDisclosureRejectedFields = $customer->getCustomAttribute('disclosure_rejected_fields')
            ->getValue();
        }
        if ($customer->getCustomAttribute('disclosure_rejected_reason')) {
            $accountDisclosureRejectedReason = $customer->getCustomAttribute('disclosure_rejected_reason')->getValue();
        }
        if ($accountDisclosureApprovalStatus == 7) {
            $statusMessage = $this->scopeConfig->
            getValue("bat_customer_disclosure/general/account_disclosure_approved_message");
        } elseif ($accountDisclosureApprovalStatus == 8) {
            $statusMessage = $this->scopeConfig->
            getValue("bat_customer_disclosure/general/account_disclosure_rejected_message");
        }
        if ($customer->getCustomAttribute('account_closing_date')) {
            $accountClosingDate = $customer->getCustomAttribute('account_closing_date')->getValue();
        }
        $returnStocks = $this->accountClosureProductReturn->getProducts($customerId);
        $itemArray = [];
        $returnStocks = array_unique($returnStocks);
        $totalQty = 0;
        $totalItem = 0;
        $imageEncodeUrl = '';
        $productImageDecode = [];
        $bankCardUrl = '';
        if (!empty($returnStocks)) {
            foreach ($returnStocks as $item) {
                try{    
                    $product = $this->productRepository->getById($item);
                    $qty = $this->accountClosureProductReturn->getQty($customerId, $item);
                    $qty = array_unique($qty);
                    $image = $product->getData('images');
                    if ($image != '') {
                        $productImageDecode = json_decode($image);
                    }
                    if (!empty($productImageDecode) && is_array($productImageDecode)) {
                        $data = get_object_vars($productImageDecode[0]);
                        $imageEncodeUrl = base64_encode($data['fileURL']);
                    }
                    $itemArray[] = [
                                    'sku' => $product->getSku(),
                                    'qty' => $qty[0],
                                    'product_image' => $imageEncodeUrl,
                                    'name' => $product->getName(),
                                    'uid' => base64_encode($product->getId()),
                                    'short_prod_nm' => $product->getShortProdNm(),
                                    'price' => $product->getPrice()
                                ];
                    $totalQty += $qty[0];
                    $totalItem++;
                } catch (NoSuchEntityException $e) {

                }
            }
        }
        $cardName = '';
        $base64 = '';
        $removedFilename = '';
        $encodeUrl = '';
        $newbankCardUrl = '';
        if ($customer->getCustomAttribute('bank_account_card')) {
            $bankAccountCard = $customer->getCustomAttribute('bank_account_card')->getValue();
            $mediaUrl = $this->storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $bankCardUrl = $mediaUrl.'customer'.$bankAccountCard;
            $type = pathinfo($bankAccountCard, PATHINFO_EXTENSION);
            $myFile = pathinfo($bankAccountCard);
            $cardName = $myFile['basename'];
            $removedFilename = $this->removeFilename($bankCardUrl);
            $encodeUrl = rawurlencode($cardName);
            $newbankCardUrl = $removedFilename.$encodeUrl;
            $data = file_get_contents($newbankCardUrl);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        $result = [
            'closure_status'        => $accountDisclosureApprovalStatus,
            'status_message'        => $statusMessage,
            'rejected_fields'       => $accountDisclosureRejectedFields,
            'rejected_reason'       => $accountDisclosureRejectedReason,
            'account_closing_date'  => $accountClosingDate,
            'bank_account_card'     => $base64,
            'card_name'             => $cardName,
            'return_stock'          => $itemArray,
            'return_total_item'     => $totalItem,
            'return_total_qty'      => $totalQty
        ];
        return $result;
    }

    /**
     * Get attribute option label
     *
     * @param string $attributeCode
     * @param string $optionValue
     */
    public function getCustomAttributeOptionLabel($attributeCode, $optionValue)
    {
        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attributeCode);

        if ($attribute->usesSource()) {
            $options = $attribute->getSource()->getAllOptions();

            foreach ($options as $option) {
                if ($option['value'] == $optionValue) {
                    return $option['label'];
                }
            }
        }
        return false;
    }

    /**
     * Remove file name from url
     *
     * @param string $url
     */
    public function removeFilename($url)
    {
        $file_info = pathinfo($url);
        return isset($file_info['extension'])
            ? str_replace($file_info['filename'] . "." . $file_info['extension'], "", $url)
            : $url;
    }
}
