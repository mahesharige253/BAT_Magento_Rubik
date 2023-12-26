<?php

namespace Bat\Kakao\Model;

use Magento\Framework\Model\AbstractModel;
use Bat\Kakao\Model\ResourceModel\KakaoTemplate\CollectionFactory;

class TemplateText extends AbstractModel
{
    /**
     * @var CollectionFactory
     */
    protected $kakaoTemplateCollection;

    /**
     * @param CollectionFactory $kakaoTemplateCollection
     */
    public function __construct(
        CollectionFactory $kakaoTemplateCollection
    ) {
        $this->kakaoTemplateCollection = $kakaoTemplateCollection;
    }

    /**
     * Get registration OTP message text
     *
     * @param string $templateCode
     * @param array $params
     * @return array
     */
    public function getTemplateText($templateCode, $params)
    {
        $template = $this->getTemplate($templateCode);
        if (empty($template)) {
            return [];
        }
        if ($templateCode == "RegistrationApprove_002") {
            $template['template'] = strtr($template['template'], [
                '#{outlet_name}' => $params['outlet_name'],
                '#{outlet_address}' => $params['outlet_address'],
                '#{owner_name}' => $params['owner_name'],
                '#{mobilephonenumber}' => $params['mobilephonenumber'],
                '#{outlet_id}' => $params['outlet_id'],
                '#{newpinpassword_link}' => $params['newpinpassword_link']
            ]);
        } elseif ($templateCode == "RegistrationReject_001") {
            $template['template'] = strtr($template['template'], [
                '#{registerationreject_reason}' => $params['registerationreject_reason'],
                '#{registrationresubmit_link}' => $params['registrationresubmit_link']
            ]);
        } elseif ($templateCode == "ForgotID_001") {
            $template['template'] = strtr($template['template'], [
                '#{outlet_id}' => $params['outlet_id']
            ]);
        } elseif ($templateCode == "ForgotPassword_001") {
            $template['template'] = strtr($template['template'], [
                '#{resetpasswordpin_link}' => $params['resetpasswordpin_link']
            ]);
        } elseif ($templateCode == "ClosingRequest_001") {
            $template['template'] = strtr($template['template'], [
                '#{outlet_name}' => $params['outlet_name'],
                '#{outlet_address}' => $params['outlet_address']
            ]);
        } elseif ($templateCode == "ClosingReject_001") {
            $template['template'] = strtr($template['template'], [
                '#{outlet_name}' => $params['outlet_name'],
                '#{outlet_address}' => $params['outlet_address']
            ]);
        } elseif ($templateCode == "ClosingComplete_001") {
            $template['template'] = strtr($template['template'], [
                '#{outlet_name}' => $params['outlet_name'],
                '#{outlet_address}' => $params['outlet_address']
            ]);
        } elseif ($templateCode == "ClosingReturn_001") {
            $template['template'] = strtr($template['template'], [
                '#{outlet_name}' => $params['outlet_name'],
                '#{1streturnproduct_others}' => $params['1streturnproduct_others'],
                '#{totalreturn_qty}' => $params['totalreturn_qty']
            ]);
        } elseif ($templateCode == "ReturnRequest_002") {
            $template['template'] = strtr($template['template'], [
                '#{returnrequest_date}' => $params['returnrequest_date'],
                '#{outlet_name}' => $params['outlet_name'],
                '#{outlet_address}' => $params['outlet_address'],
                '#{1streturnrequestproduct_others}' => $params['1streturnrequestproduct_others'],
                '#{totalreturnrequest_qty}' => $params['totalreturnrequest_qty']
            ]);
        } elseif ($templateCode == "ExistingFirstLogin_001") {
            $template['template'] = strtr($template['template'], [
                '#{outlet_name}' => $params['outlet_name'],
                '#{outlet_id}' => $params['outlet_id']
            ]);
        } elseif ($templateCode == "PaymentRequest_001") {
            $template['template'] = strtr($template['template'], [
                '#{salesorder_number}' => $params['salesorder_number'],
                '#{totalsalesorder_amount}' => $params['totalsalesorder_amount'],
                '#{vbabank_vbanumber}' => $params['vbabank_vbanumber']
            ]);
        } elseif ($templateCode == "SalesOrder_001") {
            $template['template'] = strtr($template['template'], [
                '#{salesorder_number}' => $params['salesorder_number'],
                '#{salesorder_date}' => $params['salesorder_date'],
                '#{1stsalesorderproduct_others}' => $params['1stsalesorderproduct_others'],
                '#{totalsalesorder_qty}' => $params['totalsalesorder_qty'],
                '#{totalsalesorder_amount}' => $params['totalsalesorder_amount'],
                '#{vbabank_vbanumber}' => $params['vbabank_vbanumber']
            ]);
        } elseif ($templateCode == "ShipmentNotice_001") {
            $template['template'] = strtr($template['template'], [
                '#{salesorder_number}' => $params['salesorder_number'],
                '#{1stsalesorderproduct_others}' => $params['1stsalesorderproduct_others'],
                '#{totalsalesorder_qty}' => $params['totalsalesorder_qty'],
                '#{outlet_address}' => $params['outlet_address'],
                '#{deliverytrackinginfo_number}' => $params['deliverytrackinginfo_number']
            ]);
        } elseif ($templateCode == "ReturnPhoto_001") {
            $template['template'] = strtr($template['template'], [
                '#{returnproductphotoupload_link}' => $params['returnproductphotoupload_link']
            ]);
        } elseif ($templateCode == "ReturnComplete_001") {
            $template['template'] = strtr($template['template'], [
                '#{returnrequest_date}' => $params['returnrequest_date'],
                '#{outlet_name}' => $params['outlet_name'],
                '#{1streturncproduct_others}' => $params['1streturncproduct_others'],
                '#{totalreturn_qty}' => $params['totalreturn_qty'],
                '#{totalreturn_amount}' => $params['totalreturn_amount']
            ]);
        } elseif ($templateCode == "MarketingAccept_001") {
            $template['template'] = strtr($template['template'], [
                '#{mktconsentaccept_date}' => $params['mktconsentaccept_date']
            ]);
        } elseif ($templateCode == "MarketingReject_001") {
            $template['template'] = strtr($template['template'], [
                '#{mktconsentreject_date}' => $params['mktconsentreject_date']
            ]);
        } elseif ($templateCode == "Marketing2Year_002") {
            $template['template'] = strtr($template['template'], [
                '#{mktconsentaccept_date}' => $params['mktconsentaccept_date']
            ]);
        } elseif ($templateCode == "AddressChangeRequest_001") {
            $template['template'] = strtr($template['template'], [
                '#{changeaddress_link}' => $params['changeaddress_link']
            ]);
        } elseif ($templateCode == "AddressChangeReject_001") {
            $template['template'] = strtr($template['template'], [
                '#{changeaddressreject_link}' => $params['changeaddressreject_link']
            ]);
        }
        return $template;
    }

    /**
     * Get template by particular key
     *
     * @param string $templateCode
     * @return array
     */
    public function getTemplate($templateCode)
    {
        $templates = $this->kakaoTemplateCollection->create()
            ->addFieldToFilter('template_code', $templateCode);
        if ($templates->getSize() > 0) {
            $template = $templates->getFirstItem();
            return [
                "template_title" => $template['template_title'],
                "template" => $template['template_content']
            ];
        }
        return [];
    }
}
