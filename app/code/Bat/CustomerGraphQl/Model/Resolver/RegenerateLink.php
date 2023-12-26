<?php
declare(strict_types=1);

namespace Bat\CustomerGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Bat\Integration\Helper\Data;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Bat\BulkOrder\Model\Resolver\CartDetails;
use Bat\Customer\Helper\Data as CustomerHelper;
use Bat\CustomerGraphQl\Helper\Data as CustomerHelperData;
use Bat\Kakao\Model\Sms as KakaoSms;
use Magento\Customer\Model\CustomerFactory;

/**
 * RegenerateLink  resolver
 */
class RegenerateLink implements ResolverInterface
{

    /**
     * @var Data
     */
    private $data;

    /**
     * @var CartDetails
     */
    private $cartDetails;

    /**
     * @var CustomerHelper
     */
    private $customerHelper;

    /**
     * @var CustomerHelperData
     */
    protected $customerHelperData;

    /**
     * @var KakaoSms
     */
    private $kakaoSms;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     *
     * @param Data $data
     * @param CartDetails $cartDetails
     * @param CustomerHelper $customerHelper
     * @param CustomerHelperData $customerHelperData
     * @param KakaoSms $kakaoSms
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        Data $data,
        CartDetails $cartDetails,
        CustomerHelper $customerHelper,
        CustomerHelperData $customerHelperData,
        KakaoSms $kakaoSms,
        CustomerFactory $customerFactory
    ) {
        $this->data = $data;
        $this->cartDetails = $cartDetails;
        $this->customerHelper = $customerHelper;
        $this->customerHelperData = $customerHelperData;
        $this->kakaoSms = $kakaoSms;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['key_id'])) {
            throw new GraphQlInputException(__('"key_id" value should be specified'));
        }
        if (empty($args['input']['type'])) {
            throw new GraphQlInputException(__('"type" value should be specified'));
        }
        if ($args['input']['type'] != 'registration_set_pinpassword') {
            throw new GraphQlInputException(__('"type" value is not correct'));
        }

        $outletId = $this->data->decryptData($args['input']['key_id']);
        $decryptFields = explode(",", $outletId);
        if ($decryptFields != $outletId) {
            $outletId = $decryptFields[0];
        }

        $customerId = $this->cartDetails->getCustomerIdsByCustomAttribute($outletId);
        $outletName = $this->customerHelper->getInfo($customerId[0]);
        $customer = $this->customerFactory->create()->load($customerId[0]);

        $outletOwnerName = $customer->getFirstname();
        $outletAddress = $this->customerHelper->getCustomerDefaultShippingAddress($customer);
        $mobileNumber = $customer->getData('mobilenumber');
        $url = $this->customerHelperData->getSetPasswordPinUrl();
        $key = $this->customerHelper->saveEncryptUrl($outletId, "registration_set_pinpassword");
        $newpinpasswordLink = $url . '&id=' . $key;

        $params = [
            'outlet_name' => $outletName,
            'owner_name' => $outletOwnerName,
            'outlet_address' => $outletAddress,
            'mobilephonenumber' => $mobileNumber,
            'outlet_id' => $outletId,
            'newpinpassword_link' => $newpinpasswordLink
        ];
        $this->kakaoSms->sendSms($mobileNumber, $params, 'RegistrationApprove_002');
        return ['message' => 'Link Regenerated', 'success' => true];
    }
}
