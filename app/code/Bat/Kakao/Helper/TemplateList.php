<?php
namespace Bat\Kakao\Helper;

class TemplateList extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Prepare and get list of templates
     *
     * @return string[]
     */
    public function getTemplateList()
    {
        return [
            'NewLink_001',
            'RegistrationRequest_001',
            'MarketingAccept_001',
            'MarketingReject_001',
            'RegistrationApprove_002',
            'RegistrationReject_001',
            'ExistingFirstLogin_001',
            'ForgotID_001',
            'ForgotPassword_001',
            'ClosingRequest_001',
            'ClosingReject_001',
            'ClosingComplete_001',
            'ClosingReturn_001',
            'SalesOrder_001',
            'PaymentRequest_001',
            'DeliveryDelay_001',
            'ShipmentNotice_001',
            'UnpaidCancel_001',
            'CustomerCancel_001',
            'ReturnPhoto_001',
            'ReturnComplete_001',
            'FrequencyChange_001',
            'CreditLimitChange_001',
            'AddressChangeRequest_001',
            'AddressChangeApprove_001',
            'AddressChangeReject_001',
            'VBAChange_001',
            'Marketing2Year_002',
            'OrderDay_001',
            'ReturnRequest_002',
            'NPI_001'
        ];
    }
}
