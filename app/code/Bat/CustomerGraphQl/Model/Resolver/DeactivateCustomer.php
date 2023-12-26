<?php
namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Bat\CustomerGraphQl\Model\Resolver\DataProvider\BankCardUpload;
use Magento\Customer\Model\CustomerFactory;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Bat\Kakao\Model\Sms as KakaoSms;
use Bat\Customer\Helper\Data;
use Bat\AccountClosure\Model\AccountClosureProductReturnFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Bat\CustomerGraphQl\Model\ReturnRequestOrder;
use Bat\AccountClosure\Model\ClosureFactory;

class DeactivateCustomer implements ResolverInterface
{
    /**
     * @var BankCardUpload
     */
    private $bankCardUpload;

    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @var KakaoSms
     */
    private KakaoSms $kakaoSms;

    /**
     * @var Data
     */
    protected Data $helperData;

    /**
     * @var AccountClosureProductReturnFactory
     */
    private $accountClosureProductReturnFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var ReturnRequestOrder
     */
    protected $returnRequestOrder;

    /**
     * @var ClosureFactory
     */
    private $closure;

    /**
     * @param BankCardUpload $bankCardUpload
     * @param GetCustomer $getCustomer
     * @param CustomerFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param KakaoSms $kakaoSms
     * @param Data $helperData
     * @param AccountClosureProductReturnFactory $accountClosureProductReturn
     * @param ProductFactory $_productFactory
     * @param ReturnRequestOrder $returnRequestOrder
     * @param ClosureFactory $closure
     */
    public function __construct(
        BankCardUpload $bankCardUpload,
        GetCustomer $getCustomer,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        KakaoSms $kakaoSms,
        Data $helperData,
        AccountClosureProductReturnFactory $accountClosureProductReturn,
        ProductFactory $_productFactory,
        ReturnRequestOrder $returnRequestOrder,
        ClosureFactory $closure
    ) {
        $this->bankCardUpload = $bankCardUpload;
        $this->getCustomer = $getCustomer;
        $this->_customerFactory = $customerFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->kakaoSms = $kakaoSms;
        $this->helperData = $helperData;
        $this->accountClosureProductReturnFactory = $accountClosureProductReturn;
        $this->_productFactory = $_productFactory;
        $this->returnRequestOrder = $returnRequestOrder;
        $this->closure = $closure;
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

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        if (!isset($args['input']['account_closing_date'])) {
            throw new GraphQlInputException(__('Account Closing Date should be specified'));
        } elseif (isset($args['input']['account_closing_date']) && ($args['input']['account_closing_date'] == '')) {
            throw new GraphQlInputException(__('Account Closing Date should be specified'));
        }

        if (!isset($args['input']['consent_form'])) {
            throw new GraphQlInputException(__('Consent form should be specified'));
        } elseif (isset($args['input']['consent_form']) && ($args['input']['consent_form'] != 1)) {
            throw new GraphQlInputException(__('Please select the required consent'));
        }

        if (!isset($args['input']['returning_stock'])) {
            throw new GraphQlInputException(__('Return to Stock value should be specified'));
        }
        $startDate = date('Y-m-d', strtotime("+6 weekday", time()));
        $endDate = date('Y-m-d', strtotime("+19 weekday", time()));
        $closingDate = $args['input']['account_closing_date'];
        if (!( $closingDate >= $startDate ) || !( $closingDate <= $endDate )) {
            throw new GraphQlInputException(__('Closing date should be in between next 7 - 20 days only'));
        }

        if (isset($args['input']['returning_stock']) && ($args['input']['returning_stock'] == 1)) {
            if (!isset($args['input']['return_items'])) {
                throw new GraphQlInputException(__('Return Items should be specified'));
            }
        }

        $customer = $this->getCustomer->execute($context);
        $customerId = $customer->getId();
        $isBankCardFileUpload = 0;

        $customerFactory = $this->_customerFactory->create()->load($customerId)->getDataModel();

        $customerApprovalStatus = (($customerFactory->getCustomAttribute('approval_status') != ''))
                                    ?$customerFactory->getCustomAttribute('approval_status')->getValue():'';

        if($customerApprovalStatus == 14) {
            throw new GraphQlInputException(__('The account closure is already requested'));
        }

        if($customerApprovalStatus == 6) {
            throw new GraphQlInputException(__('The account is already in under review of closure'));
        }

        if($customerApprovalStatus == 7) {
            throw new GraphQlInputException(__('The account is already closed account'));
        }

        if($customerApprovalStatus == 9) {
            throw new GraphQlInputException(__('The account is already terminated'));
        }

        if($customerApprovalStatus == 10) {
            throw new GraphQlInputException(__('The account is already refund in progress for closure'));
        }

        if($customerApprovalStatus == 11) {
            throw new GraphQlInputException(__('The account is already collection in progress for closure'));
        }

        /* Upload Bank Account Card file */
        if (isset($args['input']['bank_account_card'])) {
            if ((isset($args['input']['bank_account_card'][0]['card_name'])
                && ($args['input']['bank_account_card'][0]['card_name']!= ''))
                && (isset($args['input']['bank_account_card'][0]['card_file'])
                    && ($args['input']['bank_account_card'][0]['card_file'] != ''))) {
                $bankAccountCardName = $args['input']['bank_account_card'][0]['card_name'];
                $bankAccountCardFile = $args['input']['bank_account_card'][0]['card_file'];
                $bankCardResponse = $this->bankCardUpload->uploadBankAccountCard(
                    $bankAccountCardName,
                    $bankAccountCardFile,
                    $customerId
                );
                $isBankCardFileUpload = 1;
            } else {
                throw new GraphQlInputException(__('Bank account card value missing'));
            }
        } else {
                throw new GraphQlInputException(__('Bank account card value should be specified'));
        }

        if ($isBankCardFileUpload == 1) {
            $filePath = '/bankCard/'.$bankCardResponse['items'][0]['name'];
            $customerFactory = $this->_customerFactory->create()->load($customerId)->getDataModel();
            $customerFactory->setCustomAttribute('bank_account_card', $filePath);
            $customerFactory->setCustomAttribute('account_closing_date', $args['input']['account_closing_date']);
            $customerFactory->setCustomAttribute('returning_stock', $args['input']['returning_stock']);
            $customerFactory->setCustomAttribute('disclosure_consent_form_selected', $args['input']['consent_form']);
            $customerFactory->setCustomAttribute('approval_status', 14);
            $this->_customerRepositoryInterface->save($customerFactory);
        } else {
            throw new GraphQlInputException(__('Bank card file could\'t upload.'));
        }

        //Check for product return table and delete if any customer data
        $productReturnModel = $this->accountClosureProductReturnFactory->create();
        $productReturnModel->checkProductReturnData($customerId);

        if (isset($args['input']['returning_stock']) && ($args['input']['returning_stock'] == 1)) {
            $returnItems = $args['input']['return_items'];
            $outletId = $customerFactory->getCustomAttribute('outlet_id')->getValue();
            foreach ($returnItems as $item) {
                $productReturnModel = $this->accountClosureProductReturnFactory->create();
                $productId = $this->_productFactory->create()->getIdBySku($item['sku']);
                $productReturnModel->setData('outlet_id', $outletId);
                $productReturnModel->setData('customer_id', $customerId);
                $productReturnModel->setData('product_id', $productId);
                $productReturnModel->setData('qty', $item['qty']);
                $productReturnModel->save();
            }
        }

        // Track Closure Account details
        $closureModel = $this->closure->create();
        $existData = $closureModel->getIdbyCustomerId($customerId);
        if(!(count($existData) >0)) {
            $closureModel->setCustomerId($customerId);
            $closureModel->save();

            if ($customer->getCustomAttribute('mobilenumber')) {
                $mobileNumber = $customer->getCustomAttribute('mobilenumber')->getValue();
                $outletName = $this->helperData->getInfo($customerId);
                $outletAddress = $this->helperData->getCustomerDefaultShippingAddress($customer);
            }
            $this->kakaoSms->sendSms(
                $mobileNumber,
                ['outlet_name' => $outletName, 'outlet_address' => $outletAddress],
                'ClosingRequest_001'
            );
        }

        return ['success' => true , 'message' => __('Your Account Will Be Deactivated')];
    }
}
